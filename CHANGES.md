# CURRENT BUILT
Version 0.9
Built 56, 30 June 2017


# FUNCTIONALITY ROADMAP
* [ ] Include `/getiban` in individual chat
* [ ] Add `/export` to PNG command for settle or suggestion _v2.0_
* [X] Update new user response to register user
* [X] Update gone use response to delete user data for current group
* [X] Add `/settlehi` command to not require adding amount for discovery
* [X] Add command `/plus`: add 1, 2 or 3 for one group member
* [X] Allow for calculation while `/paid`; e.g. `/paid 10+5+12`
* [X] Allow for individual chat functions
* [X] Offer opportunity to exclude group chat member
* [X] Create `/suggest` command to help settle expenses

# FIXED BUGS
## High
* [X] Solve max four buttons on a row issue
* [X] Fix error if only one excessor in e.g. chat group of 3
* [X] Exclusion calculus error
* [X] Fix not copying IBAN for existing user's new groups
* [X] Default excluded value = 0, also if user is currently excluded
* [X] Fix URL chars in inline buttons

## Medium
* [ ] Fix missing IBAN on user add
* [X] Solve common commands (`/help`, `/hi`, `/add`, `/list`)
* [X] Removed gimmick responses on e.g. photo change or group name change
* [X] Fix empty message when `/payments` without being registrated in any group
* [X] Fix empty message at individual `/payments` chat when empty
* [X] Mark excluded people at `/ignore` command (markdown ~~ not supported)
* [X] Set excluded to 0 for all at `/reset`
* [X] Make `/plus` command for 1 chat group, not one user for all his/ her chat groups
* [X] Set all to include after `/reset`
* [X] Fixed wrong message for `/suggest` if all paid exact same amount 

## Low
* [ ] Solve for duplicate chat group member names (Use initials? Nicknames?)
* [ ] Query whether someone is excluded when using `/payments` returning zero results
* [X] Respond to title, photo, member change [removed]
