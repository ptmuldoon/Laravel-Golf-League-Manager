<?php

namespace App\Console\Commands;

use Google\Client as GoogleClient;
use Google\Service\Drive as GoogleDrive;
use Illuminate\Console\Command;

class GoogleAuth extends Command
{
    protected $signature = 'google:auth';
    protected $description = 'Authorize Google Drive access and get a refresh token';

    public function handle()
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            $this->error('GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET must be set in .env first.');
            $this->newLine();
            $this->info('Steps to get these:');
            $this->line('1. Go to https://console.cloud.google.com/apis/credentials');
            $this->line('2. Create an OAuth 2.0 Client ID (type: Desktop app)');
            $this->line('3. Copy the Client ID and Client Secret to your .env');
            return Command::FAILURE;
        }

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->addScope(GoogleDrive::DRIVE_FILE);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');

        $authUrl = $client->createAuthUrl();

        $this->newLine();
        $this->info('Open this URL in your browser and authorize access:');
        $this->newLine();
        $this->line($authUrl);
        $this->newLine();

        $code = $this->ask('Paste the authorization code here');

        try {
            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                $this->error('Error: ' . $token['error_description']);
                return Command::FAILURE;
            }

            $refreshToken = $token['refresh_token'] ?? null;
            if (empty($refreshToken)) {
                $this->error('No refresh token received. Try again — Google may have used a cached consent.');
                return Command::FAILURE;
            }

            $this->newLine();
            $this->info('Success! Add this to your .env file:');
            $this->newLine();
            $this->line("GOOGLE_DRIVE_REFRESH_TOKEN={$refreshToken}");
            $this->newLine();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Authorization failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
