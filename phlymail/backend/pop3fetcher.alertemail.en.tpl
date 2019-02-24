From: $mailfrom$
To: $mailto$
MIME-Version: 1.0
Content-Type: multipart/alternative; boundary="_---_next_part_--_$time$==_"
Subject: $provider$ - You've got new email

--_---_next_part_--_$time$==_
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit

You chose to get alerted about new emails via a filtering rule in 
$provider$, the filter that matched is $filter$.

From: $from$
To: $to$
Subject: $subject$

--_---_next_part_--_$time$==_
Content-Type: text/html; charset=UTF-8
Content-Transfer-Encoding: 8bit

<html>
<head>
 <title>$html_provider$ - You've got new email</title>
</head>
<body>
You chose to get alerted about new emails via a filtering rule in 
$html_provider$, the filter that matched is $html_filter$.<br />
<br />
From: $html_from$<br />
To: $html_to$<br />
Subject: $html_subject$<br />
</body>
</html>

--_---_next_part_--_$time$==_--