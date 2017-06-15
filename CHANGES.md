# CURRENT BUILT
Version 0.9
Built 51, 14 June 2017


# FUNCTIONALITY ROADMAP
[ ] Include /getiban in individual chat
[ ] Add /export command - _save /settle, /suggest to PNG file, v2.0_
[X] Add /join command
[X] Command /plus: add 1, 2 or 3 for one group member
[X] Allow for calculation while /add; e.g. /add 10+5+12
[X] Allow for individual chat functions
[X] Offer opportunity to exclude group chat member
[X] Create /suggest command to help settle expenses

# FIXED BUGS
## High
[X] Solve max four buttons on a row issue
[X] Fix error if only one excessor in e.g. chat group of 3
[X] Exclusion calculus error
[X] Default excluded value = 0, also if user is currently excluded
[X] Fix URL chars in inline buttons

## Medium
[ ] Solve for duplicate chat group member names (Use initials? Nicknames?)
[X] Fix empty message when /list without being registrated in any group
[X] Mark excluded people at /ignore command (markdown ~~ not supported)
[X] Set excluded to 0 for all at /reset
[X] Make /plus command for 1 chat group, not one user for all his/ her chat groups
[X] Set all to include after /reset
[X]	Fixed wrong message for /suggest if all paid exact same amount 

## Low
[ ] Delete group left user data
[ ] Query whether someone is excluded when using /list returning zero results
[X] Respond to title, photo, member change