# self-hosted disposable email system

This disposable email solution can be hosted on your own standard PHP-webhoster. All you need is PHP with imap extension and an imap account with catch-all. The system is as simple as possible, with minimal codebase and complexity. 

## Usage
When accessing the web-app a random email address is generated for you. The page will reload until emails have arrived. You can delete emails and see the original sourcecode. 

### Example Screenshot
![screenshot](assets/screenshot.png)

## Licence
Attribution-NonCommercial 4.0 International (CC BY-NC 4.0)

https://creativecommons.org/licenses/by-nc/4.0/

## Requirements

* PHP, Version 5.3.0
* Apache 2
* [imap extension](php.net/manual/book.imap.php)
* [Composer](https://getcomposer.org/doc/00-intro.md#globally) (PHP Package Manager)

## Installation
- Clone/download this repository 

## Configuration
- configure the imap account in `index.php`
- (optionally) configure the link redirection provider (to keep the existence of your installation secret) in `index.php`
 
## TODO
 1. auto-delete old messages
 2. security audit against xss/sqli


## development environment
There is a Vagrantfile to be used with [vagrant](https://www.vagrantup.com/). 

