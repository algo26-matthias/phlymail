From: $mailfrom$
To: $mailto$
MIME-Version: 1.0
Content-Type: multipart/alternative; boundary="_---_next_part_--_$time$==_"
Subject: $provider$ - Вы получили новое(ые) сообщение(я)

--_---_next_part_--_$time$==_
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit

Вы установили настройки $provider$ так, что бы получать извещения о новых сообщениях.
Установленый фильтр $filter$.

От: $from$
Кому: $to$
Тема: $subject$

--_---_next_part_--_$time$==_
Content-Type: text/html; charset=UTF-8
Content-Transfer-Encoding: 8bit

<html>
<head>
 <title>$html_provider$ - Вы получили новое(ые) сообщение(я)</title>
</head>
<body>
Вы установили настройки $html_provider$ так, что бы получать извещения о новых сообщениях.
Установленый фильтр $html_filter$.<br />
<br />
От: $html_from$<br />
Кому: $html_to$<br />
Тема: $html_subject$<br />
</body>
</html>

--_---_next_part_--_$time$==_--