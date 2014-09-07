- Fix database again and again

- Improve search support (search only between followed (users|projects), language filter, etc.)
- Create search page
- Create backend search engine with optimizations (searching with like sucks)

[!] Re-enable SSE in jclass.js, understand why it doesn't work and fix the SSE freeze other ajax requests
    => Will be re-enabled when the Go-API is ready, since php locks everyting
- Fix blacklist: A project owner should see posts on - and receive notifications about - his open projects even if he put the post's author into his blacklist.
- Add project blacklist [!!]: certain users shouldn't be able to post, even in a open project. The project's owner should be able to decide who posts where.
- Complete hashtag support: automatically parse every N.html request and add hashtag (api js)
- Add group creation datetime
- Add notification when a group owner delete his account and the ownership of the group is transfered to another user
- remove news chechbox (and support server side) when the user is visiting the homepage or another profile (since news can be posted only in his own board)
- fix update message ( send "inHome" parameter or totally change the updte handling): mantain the same style
