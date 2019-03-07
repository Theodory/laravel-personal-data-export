<?php

namespace Spatie\PersonalDataDownload\Tests;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Spatie\PersonalDataDownload\Jobs\CreatePersonalDataDownloadJob;
use Spatie\PersonalDataDownload\Tests\TestClasses\User;
use Symfony\Component\HttpFoundation\Response;

class PersonalDataDownloadControllerTest extends TestCase
{
    /** @var \Illuminate\Foundation\Auth\User */
    protected $user;

    /** @var string */
    protected $downloadUrl;

    public function setUp(): void
    {
        parent::setUp();

        Storage::fake($this->diskName);

        $this->user = factory(User::class)->create();

        $zipFilename = $this->createPersonalDataDownload($this->user);

        $this->downloadUrl = route('personal-data-downloads', $zipFilename);
    }

    /** @test */
    public function it_can_download_the_personal_data_download()
    {
        $this
            ->actingAs($this->user)
            ->get($this->downloadUrl)
            ->assertSuccessful();
    }

    /** @test */
    public function it_can_not_download_personal_data_from_other_users()
    {
        $anotherUser = factory(User::class)->create();

        $this
            ->actingAs($anotherUser)
            ->get($this->downloadUrl)
            ->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /** @test */
    public function guests_cannot_download_personal_data()
    {
        $this
            ->get($this->downloadUrl)
            ->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** @test */
    public function it_returns_a_404_for_zipfiles_that_dont_exists()
    {
        $this
            ->actingAs($this->user)
            ->get($this->downloadUrl . 'invalid')
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    protected function createPersonalDataDownload(User $user): string
    {
        dispatch(new CreatePersonalDataDownloadJob($user));

        $allFiles = Storage::disk($this->diskName)->allFiles();

        return Arr::last($allFiles);
    }
}