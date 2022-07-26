# JWT token generator for Jitsi meetings in docker

#docker

V2: lighter, only in php and html, with icalendar option, sent by email. 

For docker installation just download the 2 files from above: *docker-compose.yml.SAMPLE* and *.env.SAMPLE* and rename them to *docker-compose.yml* and *.env* Adjust your variables accordingly in .env file, then run the *docker-compose up -d*, and access the interface from your browser. Use reverse proxy to secure your connection, if you want to make available from internet

#API
not available in this version. if you want api, use first version https://github.com/gpopesc/jitsi-token 

![2022-07-26_16-23](https://user-images.githubusercontent.com/11590919/181018137-79dbd88d-2135-4165-acfb-cbdf86c1beea.png)


This docker build use PHPMailer(https://github.com/PHPMailer/PHPMailer) for sending the emails and Yourls (https://yourls.org/) URL shortener (optional)

Install your jitsi meeting in docker from here: https://jitsi.github.io/handbook/docs/devops-guide/devops-guide-docker/

