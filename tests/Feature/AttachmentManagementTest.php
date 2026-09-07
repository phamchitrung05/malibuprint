<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Jobs\UploadManagedFileToGoogleDrive;
use App\Livewire\ManageAttachments;
use App\Models\Customer;
use App\Models\ManagedFile;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\User;
use App\Services\AttachmentManager;
use App\Support\StatusApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AttachmentManagementTest extends TestCase
{
    // Các test dùng disk và queue giả để không phát sinh file hoặc job thật trong test suite.
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('attachments.staging_disk', 'attachment_staging');
        config()->set('attachments.target_disk', 'google');
        Storage::fake('attachment_staging');
        Storage::fake('google');
    }

    public function test_staged_file_is_linked_to_order_and_queued_after_order_exists(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $order = $this->createOrder($user);
        $path = 'orders/2026/09/staged-file.pdf';
        Storage::disk('attachment_staging')->put($path, 'pdf-content');

        $files = app(AttachmentManager::class)->attachStagedPaths(
            $order,
            [$path],
            [$path => 'Thiết kế.pdf'],
            userId: $user->id,
        );

        $this->assertCount(1, $files);
        $this->assertDatabaseHas('managed_files', [
            'original_name' => 'Thiết kế.pdf',
            'temporary_path' => $path,
            'status' => StatusApp::value('managed_file.status', 'pending'),
        ]);
        $this->assertDatabaseHas('attachments', [
            'managed_file_id' => $files[0]->id,
            'attachable_type' => Order::class,
            'attachable_id' => $order->id,
        ]);
        Storage::disk('attachment_staging')->assertExists($path);
        Storage::disk('google')->assertMissing($files[0]->path);
        $this->assertSame(
            route('managed-files.download', ['managedFile' => $files[0]->uuid], absolute: false),
            $files[0]->web_view_link,
        );
        // Người dùng đã đăng nhập tải được bản staging ngay cả khi worker chưa xử lý job.
        $this->actingAs($user)
            ->get($files[0]->web_view_link)
            ->assertSuccessful()
            // Symfony dùng tên ASCII dự phòng trong Content-Disposition cho trình duyệt cũ.
            ->assertDownload('Thiet ke.pdf');
        Queue::assertPushed(
            UploadManagedFileToGoogleDrive::class,
            fn (UploadManagedFileToGoogleDrive $job): bool => $job->managedFileId === $files[0]->id,
        );
    }

    public function test_upload_job_copies_file_to_target_and_deletes_staging_file(): void
    {
        $path = 'orders/2026/09/staged-file.pdf';
        Storage::disk('attachment_staging')->put($path, 'pdf-content');
        $uuid = (string) Str::uuid();
        $stagingLink = route('managed-files.download', ['managedFile' => $uuid], absolute: false);
        $managedFile = ManagedFile::create([
            'uuid' => $uuid,
            'disk' => 'google',
            'original_name' => 'Thiết kế.pdf',
            'storage_name' => 'managed-file.pdf',
            'path' => '2026/09/managed-file.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 11,
            'status' => StatusApp::value('managed_file.status', 'pending'),
            'temporary_disk' => 'attachment_staging',
            'temporary_path' => $path,
            'web_view_link' => $stagingLink,
        ]);

        (new UploadManagedFileToGoogleDrive($managedFile->id))->handle();

        $managedFile->refresh();

        Storage::disk('google')->assertExists('2026/09/managed-file.pdf');
        Storage::disk('attachment_staging')->assertMissing($path);
        $this->assertSame(StatusApp::value('managed_file.status', 'ready'), $managedFile->status);
        $this->assertNull($managedFile->temporary_path);
        $this->assertNotNull($managedFile->uploaded_at);
        $this->assertNotSame($stagingLink, $managedFile->web_view_link);
    }

    public function test_existing_ready_file_can_be_linked_to_multiple_orders(): void
    {
        $user = User::factory()->create();
        $firstOrder = $this->createOrder($user);
        $secondOrder = $this->createOrder($user);
        $managedFile = ManagedFile::create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'google',
            'drive_file_id' => 'drive-file-id',
            'original_name' => 'Dùng chung.pdf',
            'storage_name' => 'shared.pdf',
            'path' => '2026/09/shared.pdf',
            'size' => 100,
            'status' => StatusApp::value('managed_file.status', 'ready'),
            'uploaded_at' => now(),
        ]);

        $manager = app(AttachmentManager::class);
        $manager->attachExisting($firstOrder, $managedFile, userId: $user->id);
        $manager->attachExisting($secondOrder, $managedFile, userId: $user->id);

        $this->assertCount(1, $firstOrder->attachments);
        $this->assertCount(1, $secondOrder->attachments);
        $this->assertDatabaseCount('managed_files', 1);
        $this->assertDatabaseCount('attachments', 2);
    }

    public function test_attachment_panel_only_displays_files_linked_to_order(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user);
        $linkedFile = ManagedFile::create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'google',
            'original_name' => 'file-thuoc-order.pdf',
            'storage_name' => 'linked.pdf',
            'path' => '2026/09/linked.pdf',
            'size' => 100,
            'status' => StatusApp::value('managed_file.status', 'ready'),
        ]);
        ManagedFile::create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'google',
            'original_name' => 'file-khong-thuoc-order.pdf',
            'storage_name' => 'unlinked.pdf',
            'path' => '2026/09/unlinked.pdf',
            'size' => 100,
            'status' => StatusApp::value('managed_file.status', 'ready'),
        ]);
        app(AttachmentManager::class)->attachExisting($order, $linkedFile, userId: $user->id);

        Livewire::actingAs($user)
            ->test(ManageAttachments::class, [
                'attachableType' => Order::class,
                'attachableId' => $order->id,
            ])
            ->assertSee('file-thuoc-order.pdf')
            ->assertDontSee('file-khong-thuoc-order.pdf')
            ->assertDontSee('Thư viện file')
            ->assertDontSee('Đưa vào hàng chờ');
    }

    public function test_edit_order_page_displays_existing_attachments(): void
    {
        $user = User::factory()->create();
        $order = $this->createOrder($user);
        $managedFile = ManagedFile::create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'google',
            'drive_file_id' => 'existing-drive-file-id',
            'original_name' => 'file-da-lien-ket.pdf',
            'storage_name' => 'existing.pdf',
            'path' => '2026/09/existing.pdf',
            'size' => 100,
            'status' => StatusApp::value('managed_file.status', 'ready'),
            'uploaded_at' => now(),
        ]);
        app(AttachmentManager::class)->attachExisting($order, $managedFile, userId: $user->id);

        // Trang Edit phải đọc file qua Attachment thay vì chỉ hiển thị uploader staging đang trống.
        Livewire::actingAs($user)
            ->test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->assertSee('Tệp đã liên kết')
            ->assertSee('file-da-lien-ket.pdf')
            ->assertSeeHtml('order-attachment-uploader')
            ->assertDontSee('Thư viện file');
    }

    public function test_admin_can_attach_another_file_when_editing_order(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $order = $this->createOrder($user);
        $product = Product::create(['name' => 'Sản phẩm test upload Edit', 'unit' => 'cái']);
        $sku = ProductSku::create([
            'product_id' => $product->id,
            'sku_code' => 'EDIT-UPLOAD-SKU',
            'price' => 100000,
            'stock' => 10,
            'status' => 'active',
        ]);
        $order->items()->create([
            'product_sku_id' => $sku->id,
            'quantity' => 1,
            'unit_price' => 100000,
            'subtotal' => 100000,
        ]);

        Livewire::actingAs($user)
            ->test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->set('data.new_attachments', [UploadedFile::fake()->create('file-bo-sung.pdf', 100, 'application/pdf')])
            ->assertSee('file-bo-sung.pdf')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('managed_files', [
            'original_name' => 'file-bo-sung.pdf',
            'status' => StatusApp::value('managed_file.status', 'pending'),
        ]);
        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Order::class,
            'attachable_id' => $order->id,
        ]);
        Queue::assertPushed(UploadManagedFileToGoogleDrive::class);
    }

    public function test_create_order_uses_attachment_card_layout(): void
    {
        $user = User::factory()->create();

        // File tải tạm phải xuất hiện trong card riêng bên dưới uploader và có thể gỡ trước khi tạo Order.
        $component = Livewire::actingAs($user)
            ->test(CreateOrder::class)
            ->set('data.new_attachments', [UploadedFile::fake()->create('mau-card-visit.pdf', 2048, 'application/pdf')]);

        $component
            ->assertSeeHtml('order-attachment-uploader')
            ->assertSee('mau-card-visit.pdf')
            ->assertSee('2.0 MB')
            ->assertSee('Chờ upload')
            ->assertSee('Gỡ')
            ->assertDontSee('Tải xuống');

        $key = (string) array_key_first($component->get('data.new_attachments'));

        $component
            ->call('removeStagedAttachment', $key)
            ->assertDontSee('mau-card-visit.pdf');
    }

    public function test_cleanup_command_removes_staging_copy_after_successful_upload(): void
    {
        $path = 'orders/2026/09/leftover.pdf';
        Storage::disk('attachment_staging')->put($path, 'content');
        $managedFile = ManagedFile::create([
            'uuid' => (string) Str::uuid(),
            'disk' => 'google',
            'original_name' => 'leftover.pdf',
            'storage_name' => 'leftover.pdf',
            'path' => '2026/09/leftover.pdf',
            'size' => 7,
            'status' => StatusApp::value('managed_file.status', 'ready'),
            'temporary_disk' => 'attachment_staging',
            'temporary_path' => $path,
            'uploaded_at' => now(),
        ]);

        $this->artisan('attachments:cleanup-staging')->assertSuccessful();

        Storage::disk('attachment_staging')->assertMissing($path);
        $this->assertNull($managedFile->refresh()->temporary_path);
    }

    private function createOrder(User $user): Order
    {
        $customer = Customer::create([
            'name' => 'Khách thử nghiệm',
            'phone' => fake()->unique()->numerify('09########'),
        ]);

        return Order::create([
            'order_code' => 'FILE-'.Str::upper(Str::random(8)),
            'customer_id' => $customer->id,
            'order_date' => now(),
            'status' => 'pending',
            'subtotal' => 100000,
            'discount' => 0,
            'total_amount' => 100000,
            'created_by' => $user->id,
        ]);
    }
}
