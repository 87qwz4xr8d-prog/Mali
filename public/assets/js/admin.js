(function () {
    const dataNode = document.getElementById("admin-data");
    const data = dataNode ? JSON.parse(dataNode.textContent) : { users: [], departments: [] };

    const userForm = document.getElementById("user-form");
    if (userForm) {
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("user-modal"));

        function fillUser(user) {
            document.getElementById("user-modal-title").textContent = user ? "แก้ไขผู้ใช้" : "เพิ่มผู้ใช้";
            document.getElementById("user-id").value = user ? String(user.id) : "";
            document.getElementById("user-full-name").value = user ? user.full_name : "";
            document.getElementById("user-username").value = user ? user.username : "";
            document.getElementById("user-password").value = "";
            document.getElementById("user-password").required = !user;
            document.getElementById("password-help").textContent = user
                ? "เว้นว่างถ้าไม่ต้องการเปลี่ยนรหัสผ่าน"
                : "อย่างน้อย 8 ตัวอักษร";
            document.getElementById("user-role").value = user ? user.role : "employee";
            document.getElementById("user-active").value = user ? String(user.active) : "1";
            document.getElementById("user-department").value = user
                ? String(user.department_id)
                : (document.getElementById("user-department").value || "");
            modal.show();
        }

        document.getElementById("add-user").addEventListener("click", function () {
            fillUser(null);
        });
        document.querySelectorAll(".edit-user").forEach(function (button) {
            button.addEventListener("click", function () {
                const id = Number(button.dataset.id);
                const user = (data.users || []).find(function (item) { return item.id === id; });
                if (user) {
                    fillUser(user);
                }
            });
        });
        document.querySelectorAll(".delete-user").forEach(function (button) {
            button.addEventListener("click", async function () {
                const confirmed = await window.App.popup.fire({
                    icon: "warning",
                    title: "ลบผู้ใช้นี้?",
                    text: button.dataset.name || "",
                    showCancelButton: true,
                    confirmButtonText: "ลบ",
                    confirmButtonColor: "#b42318",
                });
                if (!confirmed.isConfirmed) {
                    return;
                }
                const body = new FormData();
                body.set("id", button.dataset.id || "");
                const result = await window.App.api("/admin/users/delete", { method: "POST", body: body });
                await window.App.showResult(result);
                if (result.ok) {
                    window.location.reload();
                }
            });
        });
        userForm.addEventListener("submit", async function (event) {
            event.preventDefault();
            const body = new FormData();
            body.set("id", document.getElementById("user-id").value);
            body.set("full_name", document.getElementById("user-full-name").value.trim());
            body.set("username", document.getElementById("user-username").value.trim());
            body.set("password", document.getElementById("user-password").value);
            body.set("role", document.getElementById("user-role").value);
            body.set("active", document.getElementById("user-active").value);
            body.set("department_id", document.getElementById("user-department").value);
            const result = await window.App.api("/admin/users", { method: "POST", body: body });
            await window.App.showResult(result);
            if (result.ok) {
                window.location.reload();
            }
        });
    }

    const departmentForm = document.getElementById("department-form");
    if (departmentForm) {
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("department-modal"));

        function fillDepartment(department) {
            document.getElementById("department-modal-title").textContent = department ? "แก้ไขแผนก" : "เพิ่มแผนก";
            document.getElementById("department-id").value = department ? String(department.id) : "";
            document.getElementById("department-name").value = department ? department.name : "";
            document.getElementById("department-color").value = department ? department.color : "#0d6efd";
            modal.show();
        }

        document.getElementById("add-department").addEventListener("click", function () {
            fillDepartment(null);
        });
        document.querySelectorAll(".edit-department").forEach(function (button) {
            button.addEventListener("click", function () {
                const id = Number(button.dataset.id);
                const department = (data.departments || []).find(function (item) { return item.id === id; });
                if (department) {
                    fillDepartment(department);
                }
            });
        });
        document.querySelectorAll(".delete-department").forEach(function (button) {
            button.addEventListener("click", async function () {
                const confirmed = await window.App.popup.fire({
                    icon: "warning",
                    title: "ลบแผนกนี้?",
                    text: button.dataset.name || "",
                    showCancelButton: true,
                    confirmButtonText: "ลบ",
                    confirmButtonColor: "#b42318",
                });
                if (!confirmed.isConfirmed) {
                    return;
                }
                const body = new FormData();
                body.set("id", button.dataset.id || "");
                const result = await window.App.api("/admin/departments/delete", { method: "POST", body: body });
                await window.App.showResult(result);
                if (result.ok) {
                    window.location.reload();
                }
            });
        });
        departmentForm.addEventListener("submit", async function (event) {
            event.preventDefault();
            const body = new FormData();
            body.set("id", document.getElementById("department-id").value);
            body.set("name", document.getElementById("department-name").value.trim());
            body.set("color", document.getElementById("department-color").value);
            const result = await window.App.api("/admin/departments", { method: "POST", body: body });
            await window.App.showResult(result);
            if (result.ok) {
                window.location.reload();
            }
        });
    }
})();