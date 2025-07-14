@chat_logs.txt look at this files tells you that the frontend @index.html has connected successfully. but here is the problem the frontend keeps on reloading everytime sending this request to the backend, can we make the frontend to only send request after 30s to ensure that is connected but to also avoid reloading, cause this drives away users using the system


otherwise if its not the frontend causing this multiple reload check the backend @chat_server.php and identify where we are having this mutliple request being sent, but prioritize the frontend @index.html to avoid the page reloading/the entire browser reloading this disrupts 
user interaction(UI)