<?php

use App\Models\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

it('allows authorized user to access api documentation', function () {
    $user = User::factory()->create(['email' => 'jonathanrr839@gmail.com']);

    $response = $this->actingAs($user)->get('/docs');

    $response->assertOk();
});

it('denies unauthorized users access to api documentation', function () {
    $user = User::factory()->create(['email' => 'other@example.com']);

    $response = $this->actingAs($user)->get('/docs');

    $response->assertForbidden();
    $response->assertSee('Unauthorized access to API documentation.');
});

it('requires authentication to access api documentation', function () {
    $response = $this->get('/docs');

    $response->assertRedirect('/login');
});

it('allows authorized user to access openapi json', function () {
    $user = User::factory()->create(['email' => 'jonathanrr839@gmail.com']);

    $response = $this->actingAs($user)->get('/docs.json');

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/json');
});

it('protects every documentation route', function (string $uri) {
    $this->get($uri)->assertRedirect('/login');

    $otherUser = User::factory()->create(['email' => 'other@example.com']);
    $this->actingAs($otherUser)->get($uri)->assertForbidden();

    $authorizedUser = User::factory()->create(['email' => 'jonathanrr839@gmail.com']);
    $this->actingAs($authorizedUser)->get($uri)->assertOk();
})->with(['/docs', '/docs.json', '/docs.openapi', '/docs.postman']);

it('does not leave documentation where the web server can serve it without the gate', function () {
    expect(public_path('docs'))->not->toBeDirectory();

    $publicScribeFiles = collect(File::allFiles(public_path('vendor/scribe')))
        ->map(fn (SplFileInfo $file): string => $file->getExtension())
        ->unique();

    expect($publicScribeFiles->intersect(['html', 'php', 'json', 'yaml']))->toBeEmpty();
});

it('does not include the api prefix in the documented base url', function () {
    expect(config('scribe.base_url'))->not->toEndWith('/api');

    $openApiSpec = File::get(Storage::disk('local')->path('scribe/openapi.yaml'));

    expect($openApiSpec)->not->toMatch('#^\s*-?\s*url: \S+/api\'?$#m');
});

it('documents urls with a single api prefix', function (string $uri) {
    $user = User::factory()->create(['email' => 'jonathanrr839@gmail.com']);

    $response = $this->actingAs($user)->get($uri);
    $content = $response->baseResponse instanceof BinaryFileResponse
        ? File::get($response->baseResponse->getFile()->getPathname())
        : $response->getContent();

    expect($content)->not->toContain('api/api');
})->with(['/docs', '/docs.openapi', '/docs.postman']);
