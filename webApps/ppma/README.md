# Test suite PPMA (version 0.6.0)

### Run the application

Execute the Bash script to initialize the Docker image containing the web application:

`./run-docker.sh`

Inside the container, start the Apache server with PHP and MySQL:

`./run-services-docker.sh`

The application shall run at the address:

`http://localhost:3000/ppma`

### Admin Credentials
username: `admin`

password: `admin`

### Stop application and remove container
Type `^C` in the terminal and then type `exit` to exit from the container. In order to remove the container type `docker rm $(docker ps -aq)`. The command will remove all stopped containers.