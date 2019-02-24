<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="content-type" content="text/html; charset={charset}"></meta>
        <title>{head_invite}</title>
    </head>
    <body dir="{html_bidi}">
        {msg_prologue}<br />
        <br />
        <table border="0" cellpadding="2" cellspacing="0">
            <thead>
                <tr>
                    <th colspan="2">{head_invite}</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{msg_title}</td>
                    <td>{title}</td>
                </tr>
                <tr>
                    <td>{msg_location}</td>
                    <td>{location}</td>
                </tr>
                <tr>
                    <td>{msg_type}</td>
                    <td>{type}</td>
                </tr>
                <tr>
                    <td>{msg_when}</td>
                    <td>{when}</td>
                </tr>
                <tr>
                    <td>{msg_status}</td>
                    <td>{status}</td>
                </tr>
                <tr>
                    <td>{msg_desc}</td>
                    <td>{description}</td>
                </tr>
            </tbody>
        </table><br />
        <br />
        {about_rsvp}<br />
        <br />
        <a href="{link_rsvp_yes}">{msg_rsvp_yes}</a>
        *
        <a href="{link_rsvp_no}">{msg_rsvp_no}</a>
        *
        <a href="{link_rsvp_maybe}">{msg_rsvp_maybe}</a>
    </body>
</html>