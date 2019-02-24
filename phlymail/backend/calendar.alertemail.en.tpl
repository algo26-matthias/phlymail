From: {from}
To: {to}
{msgid}
MIME-Version: 1.0
Content-Type: multipart/alternative; boundary="_---_next_part_--_{time}==_"
Subject: {subject}

--_---_next_part_--_{time}==_
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit

Title: {title}
Location: {location}
Start: {start}
End: {end}
Ende: {end}<!-- START reminder -->
Reminder: {reminder}<!-- END reminder -->
Description: {desc}

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
  <td align="left" valign="top"><strong>Title:</strong></td>
  <td align="left" valign="top">{title}</td>
 </tr>
 <tr>
  <td align="left" valign="top"><strong>Location:</strong></td>
  <td align="left" valign="top">{location}</td>
 </tr>
 <tr>
  <td align="left" valign="top"><strong>Start:</strong></td>
  <td align="left" valign="top">{start}</td>
 </tr>
 <tr>
  <td align="left" valign="top"><strong>End:</strong></td>
  <td align="left" valign="top">{end}</td>
 </tr><!-- START reminder_html -->
 <tr>
  <td align="left" valign="top"><strong>Reminder:</strong></td>
  <td align="left" valign="top">{reminder_html}</td>
 </tr><!-- END reminder_html -->
 <tr>
  <td align="left" valign="top"><strong>Description:</strong></td>
  <td align="left" valign="top">{desc_html}</td>
 </tr>
</table>
</body>
</html>

--_---_next_part_--_{time}==_--