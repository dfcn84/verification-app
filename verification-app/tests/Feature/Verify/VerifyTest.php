<?php

namespace Tests\Feature\Verify;

use App\Http\Controllers\Verify\Verification;
use App\Http\Controllers\Verify\StoreVerificationResult;
use App\Http\Controllers\Verify\VerifyDocument;
use App\Http\Controllers\Verify\VerifyController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Tests\TestCase;
use DB;
use Mockery;
use Mockery\MockInterface;

class VerifyTest extends TestCase
{
    public function test_init_verification_object(): void
    {
        $response = new Verification;
        $this->assertTrue($response->data === []);
        $this->assertTrue($response->errorCode === 0);
    }

    public function test_set_value_on_verification_object(): void
    {
        $mockData = ['result' => 'verified'];
        $mockErrorCode = 200;

        $response = new Verification;
        $response->data = $mockData;
        $response->errorCode = $mockErrorCode;
        $this->assertTrue($response->data === $mockData);
        $this->assertTrue($response->errorCode === $mockErrorCode);
    }

    public function test_store_result_in_database_success(): void
    {
        $mockData = [
            'user_id' => '123',
            'result' => 'verified',
            'file_type' => 'json',
        ];
        $storeVerificationResult = new StoreVerificationResult;
        $this->assertTrue($storeVerificationResult($mockData));
        $this->assertDatabaseHas('verification_results', $mockData);
    }

    public function test_store_result_in_database_fail(): void
    {
        $mockData = [
            'result' => 'verified',
            'file_type' => 'json',
        ];
        $storeVerificationResult = new StoreVerificationResult;
        $this->assertFalse($storeVerificationResult($mockData));
    }

    public function test_store_result_in_database_queryexception(): void
    {
        $mockData = [
            'user_id' => '123',
            'result' => 'verified',
            'file_type' => 'json',
        ];
        DB::shouldReceive('table')
            ->with('verification_results')
            ->once()
            ->andThrow(new \Illuminate\Database\QueryException('', '', [], new \Exception()));

        $storeVerificationResult = new StoreVerificationResult;
        $this->assertFalse($storeVerificationResult($mockData));
    }

    public function test_missing_file()
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('hasFile')->once()->with('uploadedFile')->andReturn(false);
        $controller = new VerifyController();

        $response = $controller->store($request, new StoreVerificationResult(), new VerifyDocument());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->status());
    }

    public function test_valid_file()
    {
        $fileName = 'verify.json';
        $validFile = '{
            "data": {
              "id": "63c79bd9303530645d1cca00",
              "name": "Certificate of Completion",
              "recipient": {
                "name": "Marty McFly",
                "email": "marty.mcfly@gmail.com"
              },
              "issuer": {
                "name": "Accredify",
                "identityProof": {
                  "type": "DNS-DID",
                  "key": "did:ethr:0x05b642ff12a4ae545357d82ba4f786f3aed84214#controller",
                  "location": "ropstore.accredify.io"
                }
              },
              "issued": "2022-12-23T00:00:00+08:00"
            },
            "signature": {
              "type": "SHA3MerkleProof",
              "targetHash": "288f94aadadf486cfdad84b9f4305f7d51eac62db18376d48180cc1dd2047a0e"
            }
        }';
        $inputName = 'uploadedFile';

        $request = Mockery::mock(Request::class);
        $request->file = new \Symfony\Component\HttpFoundation\FileBag([
            'uploadedFile' => UploadedFile::fake()->createWithContent($fileName, $validFile)
        ]);
        $request->shouldReceive('hasFile')->once()->with($inputName)->andReturn(true);
        $request->shouldReceive('file')->once()->with($inputName)->andReturn(UploadedFile::fake()->createWithContent($fileName, $validFile));

        $storeVerificationResult = $this->createMock(StoreVerificationResult::class);
        $storeVerificationResult->method('__invoke')->willReturn(true);

        $verifyDocument = $this->createMock(VerifyDocument::class);
        
        $verifyDocument->method('__invoke')->willReturn(new Verification(
            ['issuer_name' => 'Accredify', 'status' => 'verified'],
            Response::HTTP_OK
        ));

        $controller = new VerifyController();

        $response = $controller->store($request, $storeVerificationResult, $verifyDocument);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->status());
    }
}
