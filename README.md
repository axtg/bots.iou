# SETTLEBOT FOR TELEGRAM

## Introduction
Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod
tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,
quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo
consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse
cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non
proident, sunt in culpa qui officia deserunt mollit anim id est laborum.

**Live version [link](http://telegram.me/settlebot)**


## Available commands 
* help - Basic info on how to use this bot
* list - Shows your list of transactions 
* settle - Calculates the latest settlement status (group)
* suggest - Offers a suggestion how to divide the payment (group)
* ignore - In- or excludes a group chat member from the settlement (group)
* plus - Adds a +1 to an individual group chat member (group)
* getiban - Shows IBANs from current chat group members

_Those that require user input_
* add <amount>
* setiban <iban>
* ignore <user>


## Compatibility
This bot was developed on an Apache 2.4.23, PHP 7.0.10, MySQL 5.7.14 stack.
All database interactions run through PHP's Data Objects (PDO) extension.
One external package (php-iban) is included using Composer for calculating valid IBAN input in /setiban.
All server generated responses are formatted using Markdown. Change parse_mode in the sendMessage function as desired. 


## Bot details
*Botname*
SettleBot

*In-app description* 
Helps you settle payments between friends. So for me to work, add me to your friends' group chat. Then simply use /add $$.$$ to add an expense made (/add 10.50), and use /settle to have me calculate who ows what.


## License
Copyright 2017 Xander Groesbeek <xander@groesbeek.at>

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

[More details on MIT license](https://tldrlegal.com/license/mit-license)
