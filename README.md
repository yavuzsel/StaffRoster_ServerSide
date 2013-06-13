"check_sync_time.php" file contains response to the client about whether a reload is required or not. for now, it is not time based (manually set) but it will soon be time based. it will send the "sync required" signal when the client is not sync, i.e. the server data is updated after the client loaded the data.

*****
Since I am planning to use AeroGear Controller soon (with JBoss), these server side codes are never designed or revisited. They are just implemented to bootstrap my mobile trials. Messy codes, you may totally skip them.