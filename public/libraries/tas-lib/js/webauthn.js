/**
 * WebAuthn Helper Functions
 */

function arrayBufferToBase64(buffer) {
    let binary = '';
    let bytes = new Uint8Array(buffer);
    let len = bytes.byteLength;
    for (let i = 0; i < len; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return window.btoa(binary);
}

function base64ToArrayBuffer(base64) {
    base64 = base64.replace(/-/g, '+').replace(/_/g, '/');
    let padLen = (4 - (base64.length % 4)) % 4;
    base64 += '='.repeat(padLen);
    let binary_string = window.atob(base64);
    let len = binary_string.length;
    let bytes = new Uint8Array(len);
    for (let i = 0; i < len; i++) {
        bytes[i] = binary_string.charCodeAt(i);
    }
    return bytes.buffer;
}

function recursiveBase64ToArrayBuffer(obj) {
    for (let key in obj) {
        if (typeof obj[key] === 'object' && obj[key] !== null) {
            recursiveBase64ToArrayBuffer(obj[key]);
        } else if (typeof obj[key] === 'string') {
            // lbuchs/WebAuthn encodes binary properties as =?BINARY?B?{base64}?=
            if (obj[key].startsWith('=?BINARY?B?') && obj[key].endsWith('?=')) {
                let base64 = obj[key].substring(11, obj[key].length - 2);
                obj[key] = base64ToArrayBuffer(base64);
            }
        }
    }
}

// Function to handle login via WebAuthn
function startWebAuthnLogin(loginUrl, processUrl, redirectUrl) {
    if (!window.PublicKeyCredential) {
        alert("Browser Anda tidak mendukung WebAuthn / Login Biometrik.");
        return;
    }

    $.ajax({
        url: loginUrl,
        type: 'GET',
        dataType: 'json',
        success: function(options) {
            if (options.error) {
                alert(options.error);
                return;
            }

            // Convert base64 fields to ArrayBuffers
            recursiveBase64ToArrayBuffer(options);

            navigator.credentials.get({ publicKey: options })
                .then(function(assertion) {
                    let authData = {
                        id: arrayBufferToBase64(assertion.rawId),
                        clientDataJSON: arrayBufferToBase64(assertion.response.clientDataJSON),
                        authenticatorData: arrayBufferToBase64(assertion.response.authenticatorData),
                        signature: arrayBufferToBase64(assertion.response.signature),
                        userHandle: assertion.response.userHandle ? arrayBufferToBase64(assertion.response.userHandle) : null
                    };

                    $.ajax({
                        url: processUrl,
                        type: 'POST',
                        data: authData,
                        dataType: 'json',
                        success: function(res) {
                            if (res.success) {
                                window.location.href = redirectUrl;
                            } else {
                                alert("Login Gagal: " + res.error);
                            }
                        },
                        error: function(err) {
                            alert("Login Error: " + (err.responseJSON ? err.responseJSON.error : "Unknown error"));
                        }
                    });
                })
                .catch(function(err) {
                    console.error(err);
                    alert("Proses dibatalkan atau gagal: " + err.message);
                });
        },
        error: function(err) {
            alert("Gagal mengambil data WebAuthn: " + err.statusText);
        }
    });
}

// Function to handle registration via WebAuthn
function startWebAuthnRegister(registerUrl, processUrl, successCallback) {
    if (!window.PublicKeyCredential) {
        alert("Browser Anda tidak mendukung WebAuthn / Biometrik.");
        return;
    }

    $.ajax({
        url: registerUrl,
        type: 'GET',
        dataType: 'json',
        success: function(options) {
            if (options.error) {
                alert(options.error);
                return;
            }

            // Convert base64 fields to ArrayBuffers
            recursiveBase64ToArrayBuffer(options);

            if (options.user && options.user.id) {
                // User ID must be Uint8Array
                if (typeof options.user.id === 'string') {
                     options.user.id = base64ToArrayBuffer(options.user.id);
                }
            }

            navigator.credentials.create({ publicKey: options })
                .then(function(credential) {
                    let attestationData = {
                        clientDataJSON: arrayBufferToBase64(credential.response.clientDataJSON),
                        attestationObject: arrayBufferToBase64(credential.response.attestationObject)
                    };

                    $.ajax({
                        url: processUrl,
                        type: 'POST',
                        data: attestationData,
                        dataType: 'json',
                        success: function(res) {
                            if (res.success) {
                                if (successCallback) successCallback();
                                else alert("Pendaftaran biometrik berhasil!");
                            } else {
                                alert("Pendaftaran Gagal: " + res.error);
                            }
                        },
                        error: function(err) {
                            alert("Pendaftaran Error: " + (err.responseJSON ? err.responseJSON.error : "Unknown error"));
                        }
                    });
                })
                .catch(function(err) {
                    console.error(err);
                    alert("Proses dibatalkan atau gagal: " + err.message);
                });
        },
        error: function(err) {
            alert("Gagal mengambil data WebAuthn: " + err.statusText);
        }
    });
}
