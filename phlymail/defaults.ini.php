;<?php die(); ?>
; DO NOT EDIT THIS FILE!
; Instead clone it to ./choices.ini.php and modify the copy!
; THIS FILE WILL BE OVERWRITTEN WITH EACH UPDATE!
[core]
debugging_level = "system"
database = "mysql"
theme_name = "Yokohama"
language = "de_Sie"
send_method = "smtp"
pagesize = 150
sms_use_gw = "phlymail.de"
sms_feature_active = 0
answer_style = compliant
sendmail = "/usr/sbin/sendmail"
folders_usepreview = 1
newmail_playsound = 0
showattachmentinline = 1
logout_emptytrash = 1
logout_emptyjunk = 1
online_status = 0
[auth]
countonfail = 3
waitonfail = 5
lockonfail = 90
[path]
base = "."
frontend = "frontend"
admin = "config"
lib = "shared/lib"
conf = "storage/config"
msggw = "shared/msggw"
theme = "frontend/themes"
message = "shared/messages"
driver = "shared/drivers"
extauth = "shared/extauth"
handler = "handlers"
storage = "storage"
userbase = "storage/user"
logging = "storage/logging"
tplcache = "storage/tplcache/"
au_tmp = "storage/autoupdate"
temp = "storage/temp"
[logging]
facility = filesystem
basename = "%Y/%m/%d.log"
log_sysauth = 0
log_sql = 0
[size]
thumb_filesize = 8388608
thumb_pixelsize = 16000000