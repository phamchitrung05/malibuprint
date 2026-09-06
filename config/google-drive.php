<?php

return [
    // OAuth credentials from Google Cloud Console.
    'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
    'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),

    // The Drive folder used as the root for MalibuPrint files.
    'folder' => env('GOOGLE_DRIVE_FOLDER'),

    // Optional values for a shared folder or Google Workspace Shared Drive.
    'shared_folder_id' => env('GOOGLE_DRIVE_SHARED_FOLDER_ID'),
    'team_drive_id' => env('GOOGLE_DRIVE_TEAM_DRIVE_ID'),
];
