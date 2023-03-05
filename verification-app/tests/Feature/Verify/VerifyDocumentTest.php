<?php

namespace Tests\Feature\Verify;

use App\Http\Controllers\Verify\VerifyDocument;
use App\Http\Controllers\Verify\StoreVerificationResult;
use App\Http\Controllers\Verify\Verification;
use App\Http\Controllers\Verify\VerificationResult;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class VerifyDocumentTest extends TestCase
{
    protected $request;
    protected $storeVerificationResult;
    protected $uploadedFile;
    protected $verification;

    public function setUp(): void
    {
        parent::setUp();

        $this->request = Mockery::mock(Request::class);
        $this->storeVerificationResult = Mockery::mock(StoreVerificationResult::class);
        $this->uploadedFile = Mockery::mock(UploadedFile::class, [
            'getClientOriginalExtension' => 'json'
        ]);
        $this->verification = Mockery::mock(Verification::class);
        $this->invalidRecipient = '{
            "data": {
              "id": "63c79bd9303530645d1cca00"
            }
        }';
        $this->invalidIssuer = '{
            "data": {
              "id": "63c79bd9303530645d1cca00",
              "name": "Certificate of Completion",
              "recipient": {
                "name": "Marty McFly",
                "email": "marty.mcfly@gmail.com"
              },
              "issued": "2022-12-23T00:00:00+08:00"
            }
        }';
        $this->invalidSignature = '{
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
            }
        }';
        $this->fullValid = '{
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
    }

    public function tearDown(): void
    {
        parent::tearDown();

        Mockery::close();
    }

    public function test_has_invalid_recipient()
    {
        $this->uploadedFile->shouldReceive('get')->andReturn($this->invalidRecipient);

        $verifyDocument = new VerifyDocument();
        $response = $verifyDocument->__invoke($this->request, $this->storeVerificationResult, $this->uploadedFile);

        $this->assertInstanceOf(Verification::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->errorCode);
        $this->assertEquals(['result' => VerificationResult::InvalidRecipent->value], $response->data);

        $this->uploadedFile->shouldReceive('get')->andReturn($this->invalidRecipient);
    }

    public function test_invalid_issuer()
    {
        $this->uploadedFile->shouldReceive('get')->andReturn($this->invalidIssuer);
        $this->verification->shouldReceive('value')->andReturn(VerificationResult::Verified->value);
        $this->storeVerificationResult->shouldReceive('__invoke')->andReturn(true);
        
        $verifyDocument = new VerifyDocument();
        $response = $verifyDocument->__invoke($this->request, $this->storeVerificationResult, $this->uploadedFile);

        $this->assertInstanceOf(Verification::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->errorCode);
        $this->assertEquals(['result' => VerificationResult::InvalidIssuer->value], $response->data);
    }

    public function test_invalid_signature()
    {
        $this->uploadedFile->shouldReceive('get')->andReturn($this->invalidSignature);

        $controller = new VerifyDocument();
        $response = $controller->__invoke($this->request, $this->storeVerificationResult, $this->uploadedFile);
        $this->assertInstanceOf(Verification::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->errorCode);
        $this->assertEquals(['result' => VerificationResult::InvalidSignature->value], $response->data);
    }

    public function test_full_valid()
    {
        $this->uploadedFile->shouldReceive('get')->andReturn($this->fullValid);
        $uploadedFile = UploadedFile::fake()->createWithContent('verify.json', $this->fullValid);
        $request = Mockery::mock(Request::class);
        $request->file = new \Symfony\Component\HttpFoundation\FileBag([
            'uploadedFile' => $uploadedFile
        ]);
        $storeVerificationResult = $this->createMock(StoreVerificationResult::class);
        $storeVerificationResult->method('__invoke')->willReturn(true);
        
        $user = User::factory()->create();
        $this->actingAs($user);
        $controller = new VerifyDocument();
        $response = $controller->__invoke($request, $storeVerificationResult, $uploadedFile);
        $this->assertInstanceOf(Verification::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->errorCode);
        $this->assertEquals(['issuer_name' => 'Accredify', 'result' => VerificationResult::Verified->value], $response->data);
    }

    public function test_dns_txt_no_answer()
    {
        $location = 'helloworld.com';
        $key = 'thekey';

        Http::fake([
            'https://dns.google/resolve?name=helloworld.com&type=TXT' => Http::response([
                'NoAnswer' => []
            ], 200),
        ]);

        
        $controller = new VerifyDocument();
        $reflectionClass = new \ReflectionClass($controller);
        $reflectionMethod = $reflectionClass->getMethod('__hasDnsTxt');
        $reflectionMethod->setAccessible(true);
        $result = $reflectionMethod->invoke($controller, $key, $location);

        $this->assertFalse($result);
    }

    public function test_invalid_dns_txt_empty()
    {
        $location = 'notme.com';
        $key = 'thekey';

        Http::fake([
            'https://dns.google/resolve?name=helloworld.com&type=TXT' => Http::response(null, 400),
        ]);

        
        $controller = new VerifyDocument();
        $reflectionClass = new \ReflectionClass($controller);
        $reflectionMethod = $reflectionClass->getMethod('__hasDnsTxt');
        $reflectionMethod->setAccessible(true);
        $result = $reflectionMethod->invoke($controller, 'thekey', 'helloworld.com');

        $this->assertFalse($result);
    }
}