(function () {
    const MONTHS = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
    const MONTHS_SHORT = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
    const DAYS = ["จันทร์", "อังคาร", "พุธ", "พฤหัสบดี", "ศุกร์", "เสาร์", "อาทิตย์"];

    const me = JSON.parse(document.getElementById("me-data").textContent);
    const departments = JSON.parse(document.getElementById("dept-data").textContent);
    const grid = document.getElementById("cal-grid");
    const title = document.getElementById("cal-title");
    const count = document.getElementById("cal-count");
    const filter = document.getElementById("department-filter");
    const modalEl = document.getElementById("event-modal");
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const form = document.getElementById("event-form");
    let events = [];
    let state = readState();

    function readState() {
        const params = new URLSearchParams(window.location.search);
        const view = params.get("view") === "week" ? "week" : "month";
        const department = params.get("department") || "";
        let cursor = new Date();
        cursor.setHours(0, 0, 0, 0);
        const date = params.get("date") || "";
        if (/^\d{4}-\d{2}-\d{2}$/.test(date)) {
            const parts = date.split("-").map(Number);
            cursor = new Date(parts[0], parts[1] - 1, parts[2]);
        }
        return { view: view, cursor: cursor, department: department };
    }

    function ymd(date) {
        const month = String(date.getMonth() + 1).padStart(2, "0");
        const day = String(date.getDate()).padStart(2, "0");
        return date.getFullYear() + "-" + month + "-" + day;
    }

    function startOfWeek(date) {
        const copy = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        const day = copy.getDay();
        const diff = day === 0 ? -6 : 1 - day;
        copy.setDate(copy.getDate() + diff);
        return copy;
    }

    function addDays(date, days) {
        const copy = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        copy.setDate(copy.getDate() + days);
        return copy;
    }

    function buddhist(year) {
        return year + 543;
    }

    function parseLocal(value) {
        const parts = String(value).trim().split(" ");
        const date = parts[0].split("-").map(Number);
        const time = (parts[1] || "00:00:00").split(":").map(Number);
        return new Date(date[0], date[1] - 1, date[2], time[0] || 0, time[1] || 0, time[2] || 0);
    }

    function toInput(value) {
        const date = value instanceof Date ? value : parseLocal(value);
        const hours = String(date.getHours()).padStart(2, "0");
        const minutes = String(date.getMinutes()).padStart(2, "0");
        return ymd(date) + "T" + hours + ":" + minutes;
    }

    function clock(date) {
        return String(date.getHours()).padStart(2, "0") + ":" + String(date.getMinutes()).padStart(2, "0");
    }

    function safeColor(color) {
        return /^#[0-9A-Fa-f]{6}$/.test(color || "") ? color : "#6c757d";
    }

    function sameDay(a, b) {
        return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();
    }

    function overlapsDay(event, day) {
        const start = new Date(day.getFullYear(), day.getMonth(), day.getDate());
        const end = addDays(start, 1);
        return parseLocal(event.start_at) < end && parseLocal(event.end_at) > start;
    }

    function rangeLabel(event) {
        const start = parseLocal(event.start_at);
        const end = parseLocal(event.end_at);
        if (sameDay(start, end)) {
            return clock(start) + "–" + clock(end);
        }
        return start.getDate() + " " + MONTHS_SHORT[start.getMonth()] + " " + clock(start) + " – " + end.getDate() + " " + MONTHS_SHORT[end.getMonth()] + " " + clock(end);
    }

    function visibleRange() {
        if (state.view === "week") {
            const start = startOfWeek(state.cursor);
            return { start: start, end: addDays(start, 7), days: 7 };
        }
        const first = new Date(state.cursor.getFullYear(), state.cursor.getMonth(), 1);
        const start = startOfWeek(first);
        return { start: start, end: addDays(start, 42), days: 42 };
    }

    function writeUrl() {
        const params = new URLSearchParams();
        params.set("view", state.view);
        params.set("date", ymd(state.cursor));
        if (state.department) {
            params.set("department", state.department);
        }
        history.replaceState(null, "", "/calendar?" + params.toString());
    }

    function heading() {
        if (state.view === "week") {
            const start = startOfWeek(state.cursor);
            const end = addDays(start, 6);
            if (start.getMonth() === end.getMonth()) {
                return start.getDate() + "–" + end.getDate() + " " + MONTHS[start.getMonth()] + " " + buddhist(start.getFullYear());
            }
            return start.getDate() + " " + MONTHS_SHORT[start.getMonth()] + " – " + end.getDate() + " " + MONTHS_SHORT[end.getMonth()] + " " + buddhist(end.getFullYear());
        }
        return MONTHS[state.cursor.getMonth()] + " " + buddhist(state.cursor.getFullYear());
    }

    function renderLegend() {
        const legend = document.getElementById("dept-legend");
        legend.replaceChildren();
        departments.forEach(function (department) {
            const item = document.createElement("span");
            const dot = document.createElement("i");
            dot.className = "dept-dot";
            dot.style.background = safeColor(department.color);
            item.append(dot, document.createTextNode(department.name));
            legend.append(item);
        });
    }

    function eventButton(event, className) {
        const button = document.createElement("button");
        button.type = "button";
        button.className = className + (event.can_edit ? " is-own" : "");
        button.style.background = safeColor(event.department_color);
        button.dataset.id = String(event.id);
        button.title = event.title + " · " + event.department_name;
        if (className === "cal-event") {
            button.textContent = clock(parseLocal(event.start_at)) + " " + event.title;
        } else {
            const time = document.createElement("small");
            time.textContent = rangeLabel(event);
            const name = document.createElement("div");
            name.textContent = event.title;
            button.append(time, name);
        }
        button.addEventListener("click", function (click) {
            click.stopPropagation();
            openEvent(event.id);
        });
        return button;
    }

    function render() {
        const range = visibleRange();
        title.textContent = heading();
        document.getElementById("view-month").classList.toggle("active", state.view === "month");
        document.getElementById("view-week").classList.toggle("active", state.view === "week");
        filter.value = state.department;
        count.textContent = "งานในช่วงนี้ " + events.length + " รายการ";
        grid.replaceChildren();
        const board = document.createElement("div");
        board.className = "cal-board";

        if (state.view === "month") {
            const month = document.createElement("div");
            month.className = "cal-month";
            const dow = document.createElement("div");
            dow.className = "cal-dow";
            DAYS.forEach(function (label) {
                const cell = document.createElement("div");
                cell.textContent = label;
                dow.append(cell);
            });
            month.append(dow);
            for (let week = 0; week < 6; week += 1) {
                const row = document.createElement("div");
                row.className = "cal-row";
                for (let dayIndex = 0; dayIndex < 7; dayIndex += 1) {
                    const day = addDays(range.start, week * 7 + dayIndex);
                    row.append(dayCell(day));
                }
                month.append(row);
            }
            board.append(month);
        } else {
            const week = document.createElement("div");
            week.className = "cal-week";
            for (let index = 0; index < 7; index += 1) {
                const day = addDays(range.start, index);
                const column = document.createElement("div");
                column.className = "cal-week-day" + (sameDay(day, new Date()) ? " is-today" : "");
                const head = document.createElement("div");
                head.className = "cal-week-head";
                head.textContent = DAYS[index] + " " + day.getDate();
                const body = document.createElement("div");
                body.className = "cal-week-body";
                events.filter(function (event) {
                    return overlapsDay(event, day);
                }).forEach(function (event) {
                    body.append(eventButton(event, "week-card"));
                });
                column.addEventListener("click", function () {
                    openCreate(day);
                });
                column.append(head, body);
                week.append(column);
            }
            board.append(week);
        }
        grid.append(board);
        writeUrl();
    }

    function dayCell(day) {
        const cell = document.createElement("div");
        const today = new Date();
        const outside = day.getMonth() !== state.cursor.getMonth();
        cell.className = "cal-day" + (outside ? " is-outside" : "") + (sameDay(day, today) ? " is-today" : "");
        const number = document.createElement("div");
        number.className = "cal-day-num";
        number.textContent = String(day.getDate());
        cell.append(number);
        events.filter(function (event) {
            return overlapsDay(event, day);
        }).forEach(function (event) {
            cell.append(eventButton(event, "cal-event"));
        });
        cell.addEventListener("click", function () {
            openCreate(day);
        });
        return cell;
    }

    function setEditable(canEdit) {
        ["event-title", "event-description", "event-start", "event-end"].forEach(function (id) {
            document.getElementById(id).readOnly = !canEdit;
        });
        document.getElementById("event-save").hidden = !canEdit;
        document.getElementById("event-lock-note").hidden = canEdit;
    }

    function openCreate(day) {
        const start = new Date(day.getFullYear(), day.getMonth(), day.getDate(), 9, 0, 0);
        const end = new Date(day.getFullYear(), day.getMonth(), day.getDate(), 10, 0, 0);
        document.getElementById("event-modal-title").textContent = "เพิ่มงาน";
        document.getElementById("event-id").value = "";
        document.getElementById("event-title").value = "";
        document.getElementById("event-description").value = "";
        document.getElementById("event-start").value = toInput(start);
        document.getElementById("event-end").value = toInput(end);
        document.getElementById("event-department").value = me.department_name;
        document.getElementById("event-owner").value = me.full_name;
        document.getElementById("event-delete").hidden = true;
        setEditable(true);
        modal.show();
    }

    function openEvent(id) {
        const event = events.find(function (item) {
            return item.id === id;
        });
        if (!event) {
            return;
        }
        document.getElementById("event-modal-title").textContent = event.can_edit ? "แก้ไขงาน" : "รายละเอียดงาน";
        document.getElementById("event-id").value = String(event.id);
        document.getElementById("event-title").value = event.title;
        document.getElementById("event-description").value = event.description || "";
        document.getElementById("event-start").value = toInput(event.start_at);
        document.getElementById("event-end").value = toInput(event.end_at);
        document.getElementById("event-department").value = event.can_edit ? me.department_name : event.department_name;
        document.getElementById("event-owner").value = event.owner_name;
        document.getElementById("event-delete").hidden = !event.can_edit;
        setEditable(event.can_edit);
        modal.show();
    }

    async function load() {
        const range = visibleRange();
        const params = new URLSearchParams();
        params.set("from", ymd(range.start));
        params.set("to", ymd(range.end));
        if (state.department) {
            params.set("department_id", state.department);
        }
        grid.textContent = "กำลังโหลดปฏิทิน...";
        const data = await window.App.api("/api/events?" + params.toString());
        if (!data.ok) {
            await window.App.showResult(data);
            events = [];
        } else {
            events = data.events || [];
        }
        render();
    }

    document.getElementById("view-month").addEventListener("click", function () {
        state.view = "month";
        load();
    });
    document.getElementById("view-week").addEventListener("click", function () {
        state.view = "week";
        load();
    });
    document.getElementById("cal-today").addEventListener("click", function () {
        state.cursor = new Date();
        state.cursor.setHours(0, 0, 0, 0);
        load();
    });
    document.getElementById("cal-prev").addEventListener("click", function () {
        state.cursor = state.view === "week"
            ? addDays(state.cursor, -7)
            : new Date(state.cursor.getFullYear(), state.cursor.getMonth() - 1, 1);
        load();
    });
    document.getElementById("cal-next").addEventListener("click", function () {
        state.cursor = state.view === "week"
            ? addDays(state.cursor, 7)
            : new Date(state.cursor.getFullYear(), state.cursor.getMonth() + 1, 1);
        load();
    });
    filter.addEventListener("change", function () {
        state.department = filter.value;
        load();
    });
    document.getElementById("add-event").addEventListener("click", function () {
        openCreate(state.cursor);
    });
    document.getElementById("event-delete").addEventListener("click", async function () {
        const id = document.getElementById("event-id").value;
        const confirmed = await window.App.popup.fire({
            icon: "warning",
            title: "ลบงานนี้?",
            text: document.getElementById("event-title").value,
            showCancelButton: true,
            confirmButtonText: "ลบ",
            confirmButtonColor: "#b42318",
        });
        if (!confirmed.isConfirmed) {
            return;
        }
        const data = await window.App.api("/api/events/delete", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ id: Number(id) }),
        });
        await window.App.showResult(data);
        if (data.ok) {
            modal.hide();
            load();
        }
    });
    form.addEventListener("submit", async function (event) {
        event.preventDefault();
        const id = document.getElementById("event-id").value;
        const payload = {
            id: id ? Number(id) : undefined,
            title: document.getElementById("event-title").value.trim(),
            description: document.getElementById("event-description").value.trim(),
            start_at: document.getElementById("event-start").value,
            end_at: document.getElementById("event-end").value,
        };
        if (!payload.title || !payload.start_at || !payload.end_at) {
            await window.App.popup.fire({
                icon: "error",
                title: "ไม่สำเร็จ",
                text: "กรุณากรอกหัวข้อ เวลาเริ่ม และเวลาสิ้นสุด",
            });
            return;
        }
        const data = await window.App.api(id ? "/api/events/update" : "/api/events", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payload),
        });
        await window.App.showResult(data);
        if (data.ok) {
            modal.hide();
            load();
        }
    });

    if ([...filter.options].some(function (option) { return option.value === state.department; })) {
        filter.value = state.department;
    } else {
        state.department = "";
    }
    renderLegend();
    load();
})();
