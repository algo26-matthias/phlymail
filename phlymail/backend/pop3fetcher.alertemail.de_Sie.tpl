From: $mailfrom$
To: $mailto$
MIME-Version: 1.0
Content-Type: multipart/alternative; boundary="_---_next_part_--_$time$==_"
Subject: $provider$ - Sie haben neue Email(s)

--_---_next_part_--_$time$==_
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit

Sie haben in $provider$ eingestellt, dass Sie über neue Mails informiert
werden möchten. Der Filter, der gegriffen hat, war $filter$.

Absender: $from$
Empfänger: $to$
Betreff: $subject$

--_---_next_part_--_$time$==_
Content-Type: text/html; charset=UTF-8
Content-Transfer-Encoding: 8bit

<html>
<head>
 <title>$html_provider$ - Sie haben neue Email(s)</title>
</head>
<body>
Sie haben in $html_provider$ eingestellt, dass Sie über neue Mails informiert
werden möchten. Der Filter, der gegriffen hat, war $html_filter$.<br />
<br />
Absender: $html_from$<br />
Empfänger: $html_to$<br />
Betreff: $html_subject$<br />
</body>
</html>

--_---_next_part_--_$time$==_--