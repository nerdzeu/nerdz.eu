- Fix database again and again

- Improve search support (search only between followed (users|projects), language filter, etc.)
- Create search page
- Create backend search engine with optimizations (searching with like sucks)

[!] Re-enable SSE in jclass.js, understand why it doesn't work and fix the SSE freeze other ajax requests
    => Will be re-enabled when the Go-API is ready, since php locks everyting
- Fix blacklist: A project owner should see posts on - and receive notifications about - his open projects even if he put the post's author into his blacklist.
- Add project blacklist [!!]: certain users shouldn't be able to post, even in a open project. The project's owner should be able to decide who posts where.
- Complete search page support: change on[lukr|bookmark|...] methods or find a hack to change plist.data('type')
- Add notification when a group owner delete his account and the ownership of the group is transfered to another user
- fix update message ( send "inHome" parameter or totally change the updte handling): mantain the same style
