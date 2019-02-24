From: {from}
To: {to}
MIME-Version: 1.0
{msgid}
Content-Type: multipart/alternative; boundary="_---_next_part_--_{time}==_"
Subject: {subject}

--_---_next_part_--_{time}==_
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit

Название: {title}
Место: {location}
Начало: {start}
Окончание: {end}<!-- START reminder -->
Напоминание: {reminder}<!-- END reminder -->
Описание: {desc}

--_---_next_part_--_{time}==_
Content-Type: text/html; charset=UTF-8
Content-Transfer-Encoding: 8bit

<html>
<head>
 <title>{subject_html}</title>
</head>
<body>
<table border="0" cellpadding="2" cellspacing="0">
 <tr>
  <td align="left" valign="top"><strong>Название:</strong></td>
  <td align="left" valign="top">{title}</td>
 </tr>
 <tr>
  <td align="left" valign="top"><strong>Место:</strong></td>
  <td align="left" valign="top">{location}</td>
 </tr>
 <tr>
  <td align="left" valign="top"><strong>Начало:</strong></td>
  <td align="left" valign="top">{start}</td>
 </tr>
 <tr>
  <td align="left" valign="top"><strong>Окончание:</strong></td>
  <td align="left" valign="top">{end}</td>
 </tr><!-- START reminder_html -->
 <tr>
  <td align="left" valign="top"><strong>Напоминание:</strong></td>
  <td align="left" valign="top">{reminder_html}</td>
 </tr><!-- END reminder_html -->
 <tr>
  <td align="left" valign="top"><strong>Описание:</strong></td>
  <td align="left" valign="top">{desc_html}</td>
 </tr>
</table>
</body>
</html>

--_---_next_part_--_{time}==_--