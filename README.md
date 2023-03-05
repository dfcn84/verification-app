## How to run the application
- Ensure you have Docker
- Ensure you have `php > 8.1`, `ext-url`, `unzip`
- Clone the repository
- run `cd verification-app`
- run `cp .env.example .env`
- run `composer upgrade`
- run `php artisan key:generate`
- run `./vendor/bin/sail up` (ensure Docker is running)
- run `./vendor/bin/sail php artisan migrate`
- run `./vendor/bin/sail npm i`
- run `./vendor/bin/sail npm run build`
- go to http://localhost/register and register a new account with an email & password
- you will be brought to the dashboard, or if your session has ended go to http://localhost/login with the email/password you registered previously.
- drag a valid JSON into the drop zone to verify

## API documentation

**URL:** `/verify`
**Method:** `POST`

**Headers:**
-   `Content-Type: multipart/form-data`
    
**Request Body:**
-   JSON file with the following structure
    
```
{
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
}
```
**Response:**
-   Status Code: `200 OK` (regardless of verification)
-   Response Body
    -   Verified
		```
		{
		  "data": {
		    "issuer": "Accredify",
		    "result": "verified"
		  }
		}
		```
	-   Invalid scenario
		-   JSON does not have valid recipient
			```
			{
			  "data": {
			    "result": "invalid_recipient"
			  }
			}
			```
		-   JSON does not have valid issuer  
			
			The value of issuer.identityProof.key (i.e. Ethereum wallet address) must be found in the DNS TXT record of the domain name specified by issuer.identityProof.location
			```
			{
			  "data": {
			    "result": "invalid_issuer"
			  }
			}
			```

		-   JSON does not have valid signature
			```
			{
			  "data": {
    			"result": "invalid_signature"
			  }
			}
			```
## Tech design considerations
https://docs.google.com/document/d/1WhxCszGPvEWGVTa0mzFNSCj74nmw9yQZVNLbYB_3PTg/edit?usp=sharing
