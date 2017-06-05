# comcast-smc-scrape
Scrapes SMC gatway used for Comcast Business Class Internet Service

Comcast, for reasons unknown, chose to disable snmp on their business class gateway.

This script scrapes the customer management web interface and writes the data to a pipe deliminated plain-text file that can be easily read by monitoring software.

It's not as good as snmp but better than nothing....

Setup and usage is simple. Just change the first few lines of the script to reflect the ip address, username and password of your gateway and the path/name of the file to write to. Then run the script.
