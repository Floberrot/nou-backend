BACKEND - NOU
========================
back end for webapp NOU  


# Requirements for developments. ‼️    
  
PHP >= 8.0.2    
  
### To install php 8.0.2  on Mac  
- `brew update`  
- `brew install php@8.1`  

You have to link your new php to your Mac, to do that :  
  
- `brew link --overwrite --force php@8.0.2 `  
  
  
Sometimes, you have to run another command to unlink or change between your different versions of php on Mac : 
  
- `brew unlink php && brew link php`
  
Test if you have the right version of php :  
  
- `php -v`     
  
# Steps to initialize and run project  
  
### Initialize the project :  
  
Initialize dependencies:    
- `composer install`  
  
⚠️ Be careful to never `composer update`  without asking the development team.  
  
Create your database:  
- `php bin/console doctrine:database:create`  
  
Implement your database:  
- `php bin/console doctrine:migrations:migrate`

⚠️ Dont't delete migrations files to avoid issues on relation or empty table.  
After doing all of these steps, you can get some data of a full db in the clone of Cdesk.  
  
Database schéma :  
![image](https://user-images.githubusercontent.com/56299873/154643839-510b370c-3dec-43aa-9142-6767ea2a22be.png)
  
### Run the project  
  
`Symfony serve` 

Your project will be connected on http://127.0.0.1:8000/.   

There are nothing interresting on the back on the url, just launch it and then you have to launch the front. (except API Doc)

  
### External file manager :
  
Cloudinary on : https://cloudinary.com/documentation/image_upload_api_reference  
  
  
### API Documentation  
  
Check our Api documentation on the url /api/doc !!



