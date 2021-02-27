# dailymotion_project

To install the project on your computer, make sure your have Docker Compose. Then run successively:

⋅⋅* git clone https://github.com/gmerialdo/dailymotion_project.git --config core.autocrlf=input

⋅⋅* cd dailymotion_project

⋅⋅* docker-compose up -d --build

Then, you can access the API (on Postman for ex) by calling http://localhost:8080/ and adding the API URLs (see api_dailymotion_documentation in the project).

To run tests enter successively:

⋅⋅* docker exec php_web composer dumpautoload

⋅⋅* docker exec php_web vendor/bin/phpunit tests

Thank you!
