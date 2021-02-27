# dailymotion_project

To install the project on your computer, make sure your have Docker Compose. Then run successively:

⋅⋅* git clone https://github.com/gmerialdo/dailymotion_project.git --config core.autocrlf=input

⋅⋅* cd dailymotion_project

⋅⋅* docker-compose up -d --build

Then see api_dailymotion_documentation to call the API (on Postman for ex). 

To run tests enter successively:

⋅⋅* docker exec php_web composer dumpautoload

⋅⋅* docker exec php_web vendor/bin/phpunit tests

Thank you!
