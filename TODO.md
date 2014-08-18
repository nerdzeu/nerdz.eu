- Fix database again and again

- Improve search support (search only between followed (users|projects), language, etc.)
- Create search page
- Create backend search engine with optimizations (searching with like sucks)

[!] Re-enable SSE in jclass.js, understand why it doesn't work and fix the SSE freeze other ajax requests
    => Will be re-enabled when the Go-API is ready, since php locks everyting
- Fix blacklist: A project owner should see posts on - and receive notifications about - his open project even if he put the author into his blacklist.
- Add project blacklist [!!]: certain users shouldn't be able to post, even in a open project. The project's owner should be able to decide who posts where.
- Add hashtag support for posts categorization -> autocategorization?
- Fix default template to change post language (create post options menu)
- Add "Post on GitHub" checkbox in ISSUE_BOARD's "New Post" form
- ^^^ edit javascript API to support new features