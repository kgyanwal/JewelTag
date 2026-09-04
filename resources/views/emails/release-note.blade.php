<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#F8F6F1;font-family:Arial,Helvetica,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#F8F6F1;padding:32px 0;">
        <tr>
            <td align="center">
                <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;">
                    <tr>
                        <td style="background:#0B3D3C;padding:24px 32px;border-bottom:3px solid #C9A24B;">
                            <span style="color:#F8F6F1;font-size:20px;font-weight:800;letter-spacing:0.02em;">JewelTag</span>
                            @if($note->version)
                                <span style="color:#E4CD8E;font-size:12px;font-weight:700;margin-left:10px;">{{ $note->version }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 4px;font-size:13px;color:#64748b;">Hi {{ $recipientName }},</p>
                            <h1 style="margin:12px 0 16px;font-size:22px;color:#0B3D3C;font-weight:800;">{{ $note->title }}</h1>
                            <div style="font-size:14px;line-height:1.7;color:#1e293b;white-space:pre-wrap;">{{ $note->body }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 32px;">
                            <a href="{{ config('app.url') }}" style="display:inline-block;background:linear-gradient(135deg,#0B3D3C,#07292A);color:#F8F6F1;padding:12px 24px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:700;border:1px solid #C9A24B;">Open JewelTag</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#F8F6F1;padding:16px 32px;font-size:11px;color:#94a3b8;">
                            You're receiving this because you're an admin on JewelTag. Questions? Reply to this email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>