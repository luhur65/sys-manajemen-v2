<html lang='en'>
  <head>
    <meta charset='UTF-8' />
    <meta name='viewport' content='width=device-width, initial-scale=1.0' />
    <meta name='color-scheme' content='light' />
    <meta name='supported-color-schemes' content='light' />
    <title>Konfirmasi</title>
  </head>
  <body
    style='margin:0;padding:0;background:#eef2f7;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;'
  >
    <!-- Preheader (hidden preview text) -->
    <div
      style='display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#ffffff;opacity:0;'
    >
      Konfirmasi permintaan Anda. Jangan bagikan email ini kepada siapapun.
    </div>

    <table
      role='presentation'
      width='100%'
      cellspacing='0'
      cellpadding='0'
      border='0'
      bgcolor='#eef2f7'
      style='margin:0;padding:0;background-color:#eef2f7;'
    >
      <tr>
        <td align='center' style='padding:28px 12px;'>
          <table
            role='presentation'
            width='600'
            cellspacing='0'
            cellpadding='0'
            border='0'
            style='width:600px;max-width:600px;font-family:Segoe UI,Roboto,Helvetica,Arial,sans-serif;'
          >
            <!-- Card -->
            <tr>
              <td
                bgcolor='#ffffff'
                style='background-color:#ffffff;border:1px solid #e5e7eb;border-radius:18px;overflow:hidden;'
              >
                <!-- Header (blue dominant, email-client safe) -->
                <table
                  role='presentation'
                  width='100%'
                  cellspacing='0'
                  cellpadding='0'
                  border='0'
                  bgcolor='#1d4ed8'
                  style='border-collapse:collapse;background:#eff6ff;background-color:#509cff;'
                >
                  <tr>
                    <td style='padding:10px 24px;color:#ffffff;'>
                      <table
                        role='presentation'
                        width='100%'
                        cellspacing='0'
                        cellpadding='0'
                        border='0'
                        style='border-collapse:collapse;'
                      >
                        <tr>
                          <td>
                            <div
                              style='width:36px;height:36px;border-radius:10px;text-align:center;line-height:36px;border-radius:999px;background-color:#ffffff;padding:4px;'
                            >
                              <img
                                src='https://taspusat-storage.s3.us-east-1.amazonaws.com/hr/IcTas-Small.png'
                                width='64'
                                alt='TAS'
                                style='display:block;width:34px;max-width:34px;height:auto;border:0;outline:none;text-decoration:none;'
                              />
                            </div></td>
                          <td style='vertical-align:middle;'>
                            <div
                              style='font-size:12px;letter-spacing:0.5px;font-weight:800;color:#dbeafe;'
                            >PT. TRANSPORINDO AGUNG SEJAHTERA</div>
                            <div
                              style='margin-top:6px;font-size:18px;line-height:1.2;font-weight:900;color:#ffffff;'
                            >RESET PASSWORD</div>
                            <div
                              style='margin-top:6px;font-size:12.5px;line-height:1.4;color:#dbeafe;'
                            >Notifikasi ini dibuat otomatis oleh sistem.</div>
                          </td>
                          <td
                            align='right'
                            style='vertical-align:middle;padding-left:12px;'
                          >
                            <!-- Checkmark badge -->
                            <table
                              role='presentation'
                              cellspacing='0'
                              cellpadding='0'
                              border='0'
                              style='border-collapse:collapse;'
                            >
                              <tr>
                                <td
                                  align='center'
                                  bgcolor='#ffffff'
                                  style='width:46px;height:46px;border-radius:999px;background-color:#ffffff;'
                                >
                                  <span
                                    style='display:inline-block;font-size:22px;line-height:46px;font-weight:900;color:#1d4ed8;'
                                  >!</span>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                </table>

                <!-- Body -->
                <table
                  role='presentation'
                  width='100%'
                  cellspacing='0'
                  cellpadding='0'
                  border='0'
                  style='border-collapse:collapse;'
                >
                  <tr>
                    <td style='padding:18px 24px 10px 24px;color:#111827;'>
                      <div
                        style='font-size:14px;line-height:1.65;color:#111827;'
                      >
                        <div
                          style='padding:12px 14px;border:1px solid #bfdbfe;border-radius:14px;background:#eff6ff;color:#1e40af;'
                        >
                          <div style='font-weight:900;'>Penting! Jangan bagikan
                            email ini kepada siapapun.</div>
                          <div
                            style='margin-top:6px;font-weight:700;color:#1d4ed8;'
                          >Link ini bersifat rahasia dan terbatas waktu.</div>
                        </div>
                        <div style='margin-top:12px;color:#374151;'>Halo
                            <span
                              style='font-weight:900;color:#111827;'
                            ><?= esc($userName) ?></span></div>
                        <div style='margin-top:6px;color:#374151;'>Silahkan klik
                          tombol di bawah untuk reset password:</div>
                      </div>
                    </td>
                  </tr>

                  <tr>
                    <td align='center' style='padding:14px 24px 6px 24px;'>
                      <a
                        href='<?= $resetLink ?>'
                        style='display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-weight:900;font-size:14px;letter-spacing:0.3px;padding:12px 18px;border-radius:14px;border:1px solid #1d4ed8;'
                      >Reset Password</a>
                    </td>
                  </tr>

                  <tr>
                    <td
                      style='padding:10px 24px 18px 24px;color:#6b7280;font-size:12.5px;line-height:1.6;'
                    >
                      <div>Jika tombol tidak bisa diklik, gunakan tautan
                        berikut:</div>
                      <div style='margin-top:6px;word-break:break-all;'>
                        <a
                          href='<?= $resetLink ?>'
                          style='color:#2563eb;text-decoration:underline;font-weight:700;'
                        ><?= $resetLink ?></a>
                      </div>
                      <div style='margin-top:12px;color:#374151;'>Link
                        kedaluwarsa dalam 60 menit, atau sesaat setelah password
                        diganti. Harap ganti password anda segera.</div>
                    </td>
                  </tr>
                </table>
              </td>
            </tr>

            <!-- Footer -->
            <tr>
              <td
                style='padding:14px 4px 0 4px;text-align:center;color:#9ca3af;font-size:12px;line-height:1.5;'
              >
                &copy; <?= date('Y') ?> SYS TRANSPORINDO. All rights reserved.
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
