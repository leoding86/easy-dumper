#simple-dumper

###Tumblr dumper

Require:
-S --- Server name
-a --- Action name
-s --- Save path
-b --- blog name with .tumblr.com

Additional:
-p --- proxy [SS only for now]
-t --- post type video|photo [video, photo only for now]
-r --- retry time [default: 3]
-m --- dump max count [default: 0, unlimit]

```
php service.php -S Tumblr -k {API_KEY} -a dump -s {SAVE_PATH} -b {BLOG1[,BLOG2]} [-p {SS_PROXY} -t {POST_TYPE} -r {RETRY_TIMES} -m {MAX_COUNT}]
```