<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

class SetupOauthCommand extends Command
{
    protected $signature = 'acopio:setup-oauth';

    protected $description = 'Generate Passport keys and create the default OAuth clients (SPA public + internal confidential).';

    public function handle(ClientRepository $clients): int
    {
        if (file_exists(storage_path('oauth-private.key')) && file_exists(storage_path('oauth-public.key'))) {
            $this->components->info('Passport keys already exist.');
        } else {
            $this->call('passport:keys');
        }

        $this->ensureSpaClient($clients);
        $this->ensureInternalClient($clients);

        return self::SUCCESS;
    }

    private function ensureSpaClient(ClientRepository $clients): void
    {
        $spaName = 'ACOPIO SPA';
        $spaClientId = config('services.acopio.spa_client_id');

        $spa = $spaClientId !== null ? Passport::client()->find($spaClientId) : null;

        if ($spa === null) {
            $spa = Passport::client()->where('name', $spaName)->first();
        }

        if ($spa === null) {
            $redirectUri = $this->frontendRedirectUri();

            $spa = $spaClientId !== null
                ? $this->createSpaClient($spaName, $redirectUri, $spaClientId)
                : $clients->createAuthorizationCodeGrantClient($spaName, [$redirectUri], false);

            $this->components->info("SPA public client [{$spaName}] created (PKCE, no secret).");

            return;
        }

        $this->syncRedirectUri($spa);

        if ($spaClientId !== null && $spa->id !== $spaClientId) {
            $spa->forceFill(['id' => $spaClientId])->save();
            $this->components->info("SPA client [{$spaName}] id aligned to configured value.");

            return;
        }

        $this->components->info("SPA client [{$spaName}] already exists.");
    }

    private function ensureInternalClient(ClientRepository $clients): void
    {
        $internalName = 'ACOPIO Internal Tests';
        $internal = Passport::client()->where('name', $internalName)->first();

        if ($internal === null) {
            $internal = $clients->createPasswordGrantClient($internalName, null, true);
            $this->components->info("Internal confidential client [{$internalName}] created.");
            $this->components->warn('Client ID: '.$internal->id);
            $this->components->warn('Client secret (store it now, it will not be shown again): '.$internal->plainSecret);
        } else {
            $this->components->info("Internal client [{$internalName}] already exists.");
        }
    }

    private function createSpaClient(string $name, string $redirectUri, string $clientId): Client
    {
        $model = Passport::client();
        $columns = $model->getConnection()->getSchemaBuilder()->getColumnListing($model->getTable());

        $model->forceFill([
            'id' => $clientId,
            'name' => $name,
            'secret' => null,
            'provider' => null,
            'revoked' => false,
            ...(in_array('redirect_uris', $columns) ? [
                'redirect_uris' => [$redirectUri],
            ] : [
                'redirect' => $redirectUri,
            ]),
            ...(in_array('grant_types', $columns) ? [
                'grant_types' => ['authorization_code', 'refresh_token'],
            ] : [
                'personal_access_client' => false,
                'password_client' => false,
            ]),
        ]);

        $model->save();

        return $model;
    }

    private function syncRedirectUri(Client $client): void
    {
        $redirectUri = $this->frontendRedirectUri();
        $columns = $client->getConnection()->getSchemaBuilder()->getColumnListing($client->getTable());

        if (in_array('redirect_uris', $columns)) {
            if ($client->redirect_uris !== [$redirectUri]) {
                $client->forceFill(['redirect_uris' => [$redirectUri]])->save();
            }

            return;
        }

        if ($client->redirect !== $redirectUri) {
            $client->forceFill(['redirect' => $redirectUri])->save();
        }
    }

    private function frontendRedirectUri(): string
    {
        return (string) config('app.frontend_url', 'http://localhost:3000').'/auth/callback';
    }
}
