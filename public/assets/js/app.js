(function () {
    const Popup = Swal.mixin({
        confirmButtonText: "ตกลง",
        cancelButtonText: "ยกเลิก",
        confirmButtonColor: "#16324f",
        reverseButtons: true,
    });

    function csrf() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute("content") || "" : "";
    }

    async function api(url, options) {
        const settings = options || {};
        const headers = Object.assign(
            {
                Accept: "application/json",
                "X-CSRF-Token": csrf(),
            },
            settings.headers || {}
        );
        let response;
        try {
            response = await fetch(url, Object.assign({}, settings, { headers: headers }));
        } catch (error) {
            await Popup.fire({
                icon: "error",
                title: "ไม่สำเร็จ",
                text: "ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้",
            });
            throw error;
        }

        let data = null;
        try {
            data = await response.json();
        } catch (error) {
            data = null;
        }
        if (!data || typeof data !== "object") {
            await Popup.fire({
                icon: "error",
                title: "ไม่สำเร็จ",
                text: "เซิร์ฟเวอร์ตอบกลับไม่ถูกต้อง",
            });
            throw new Error("bad response");
        }
        if (response.status === 401 && !String(url).includes("/login")) {
            await Popup.fire({
                icon: "warning",
                title: data.title || "กรุณาเข้าสู่ระบบ",
                text: data.message || "เซสชันหมดอายุ",
            });
            window.location.href = "/login";
            throw new Error("unauthorized");
        }
        return data;
    }

    function showResult(data) {
        let icon = "success";
        if (!data.ok) {
            icon = "error";
        } else if (data.google === "not_configured" || data.google === "failed") {
            icon = "warning";
        }
        return Popup.fire({
            icon: icon,
            title: data.title || (data.ok ? "สำเร็จ" : "ไม่สำเร็จ"),
            text: data.message || "",
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        const boot = document.getElementById("boot-swal");
        if (boot) {
            let payload = {};
            try {
                payload = JSON.parse(boot.textContent || "{}");
            } catch (error) {
                payload = {};
            }
            const onceKey = payload.onceKey;
            delete payload.onceKey;
            if (payload.title && (!onceKey || !sessionStorage.getItem(onceKey))) {
                if (onceKey) {
                    sessionStorage.setItem(onceKey, "1");
                }
                Popup.fire(payload);
            }
        }

        const loginForm = document.getElementById("login-form");
        if (loginForm) {
            loginForm.addEventListener("submit", async function (event) {
                event.preventDefault();
                const body = new FormData(loginForm);
                const data = await api("/login", { method: "POST", body: body });
                if (!data.ok) {
                    await showResult(data);
                    return;
                }
                window.location.href = data.redirect || "/calendar";
            });
        }
    });

    window.App = {
        api: api,
        showResult: showResult,
        popup: Popup,
    };
})();
