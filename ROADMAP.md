## Thoughts on refactoring and improvements
- Verification of condition could have been built in a more modular way where by new verification condition can just extend a base class (since the standard output stucture is the same
- Checking of file type could be built as a method so we can add more file types
- Similarly for hashing, building as a method allow us to change to a more secure hashing algorithm
- to improve security, request and response could be encrypted via JWT/JWK like how SingPass transmit data
