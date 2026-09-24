import { hashSync } from "bcryptjs";
import type { DatabaseSync } from "node:sqlite";
import { buddhistYear } from "../format";

const PASSWORD = "Mali@2569";

function run(db: DatabaseSync, sql: string, params: unknown[] = []) {
  return db.prepare(sql).run(...params);
}

function get<T>(db: DatabaseSync, sql: string, params: unknown[] = []) {
  return db.prepare(sql).get(...params) as T | undefined;
}

export function seedIfEmpty(db: DatabaseSync) {
  const row = get<{ c: number }>(db, "SELECT COUNT(*) AS c FROM users");
  if (row && row.c > 0) return;
  seed(db);
}

export function seed(db: DatabaseSync) {
  const hash = hashSync(PASSWORD, 10);
  const now = new Date().toISOString();
  const year = buddhistYear();

  run(
    db,
    `INSERT INTO sites (company_name, customer_name, site_name, notes) VALUES
      ('บจก.ยุธาภัคร์', 'ยุธาภัคร์ (สำนักงานใหญ่)', 'หนองใหญ่ จ.ชลบุรี', 'อู่ซ่อมหลัก 168 หมู่ 5 ต.หนองเสือช้าง'),
      ('บจก.ยุธาภัคร์', 'ยุธาภัคร์ (อมตะ)', 'อมตะนคร จ.ชลบุรี', 'สาขาย่อย / จอดรถ'),
      ('บจก.ไทยซัมมิท โฮทไฮน์', 'ไทยซัมมิท', 'ปลวกแดง จ.ระยอง', 'ลูกค้าเช่าระยะยาว'),
      ('บจก.มิตซูบิชิ อีเล็คทริค', 'MELCO', 'อมตะซิตี้ จ.ชลบุรี', 'งานติดตั้งในโรงงาน'),
      ('บจก.เอสซีจี แพคเกจจิ้ง', 'SCG', 'พระประแดง จ.สมุทรปราการ', 'งานคลังสินค้า')`,
  );

  run(
    db,
    `INSERT INTO teams (name, repair_group, scope, email, notes) VALUES
      ('Team A', 'Service', 'ทุกประเภทงาน', 'service@yutapak.com', 'ทีมออกหน้างานลูกค้า'),
      ('Team B', 'Maintenance', 'ทุกประเภทงาน', 'mtn@yutapak.com', 'ทีมอู่หนองใหญ่')`,
  );

  const teamA = 1;
  const teamB = 2;
  const siteHq = 1;

  const users: Array<Record<string, unknown>> = [
    {
      username: "admin",
      name_th: "อรรควุฒิ ศรีชู",
      name_en: "Akkawut Sreechoo",
      email: "akkawut@yutapak.com",
      role: "Admin",
      team_id: teamB,
      site_id: null,
      employee_code: "YT001",
      position: "Assistant Manager",
      department: "Engineering",
      phone: "093-528-9554",
      started_at: "2018-03-01",
    },
    {
      username: "engineer",
      name_th: "วิชัย ตั้งตรง",
      name_en: "Wichai Tangtrong",
      email: "engineer@yutapak.com",
      role: "Engineer Review",
      team_id: teamB,
      site_id: null,
      employee_code: "YT002",
      position: "Engineer",
      department: "Engineering",
      phone: "061-423-2797",
      started_at: "2019-06-15",
    },
    {
      username: "manager",
      name_th: "นฤมล ศรีสุข",
      name_en: "Naruemon Srisuk",
      email: "manager@yutapak.com",
      role: "Manager Approve",
      team_id: teamA,
      site_id: null,
      employee_code: "YT003",
      position: "Service Manager",
      department: "Engineering",
      phone: "033-000-954",
      started_at: "2016-01-10",
    },
    {
      username: "tech",
      name_th: "สมชาย ใจดี",
      name_en: "Somchai Jaidee",
      email: "tech@yutapak.com",
      role: "Technician",
      team_id: teamB,
      site_id: siteHq,
      employee_code: "YT011",
      position: "technician",
      department: "Engineering",
      phone: "080-005-6314",
      started_at: "2021-02-01",
    },
    {
      username: "tech2",
      name_th: "อนุชา พานิช",
      name_en: "Anucha Phanich",
      email: "tech2@yutapak.com",
      role: "Technician",
      team_id: teamA,
      site_id: null,
      employee_code: "YT012",
      position: "technician",
      department: "Engineering",
      phone: "065-237-9720",
      started_at: "2022-08-20",
    },
    {
      username: "requestor",
      name_th: "กัญญา แสงทอง",
      name_en: "Kanya Saengthong",
      email: "planning@yutapak.com",
      role: "Requestor",
      team_id: null,
      site_id: null,
      employee_code: "YT021",
      position: "Planning Control",
      department: "Planning Control",
      phone: "033-000-954",
      started_at: "2020-11-01",
    },
    {
      username: "store",
      name_th: "มณี คลังอะไหล่",
      name_en: "Manee Klang",
      email: "store@yutapak.com",
      role: "STORE",
      team_id: null,
      site_id: siteHq,
      employee_code: "YT031",
      position: "Clerk EN & Store",
      department: "Engineering",
      phone: "033-000-954",
      started_at: "2019-04-12",
    },
  ];

  for (const u of users) {
    run(
      db,
      `INSERT INTO users (username, password_hash, name_th, name_en, email, role, team_id, site_id, employee_code, position, department, phone, started_at, active, created_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)`,
      [
        u.username,
        hash,
        u.name_th,
        u.name_en,
        u.email,
        u.role,
        u.team_id,
        u.site_id,
        u.employee_code,
        u.position,
        u.department,
        u.phone,
        u.started_at,
        now,
      ],
    );
  }

  const assets = [
    [1, "Boom Lift", "Genie", "YB002", "Z45/25J", "Z452515D-322", "Genie", "2019-05-12", "2022-05-12", "AST-YB002", "บูมข้อพับไฟฟ้า 16 ม.", 1850000, "อรรควุฒิ ศรีชู", "093-528-9554", "รถหลักอู่หนองใหญ่", 2480, "พร้อมใช้งาน"],
    [1, "Boom Lift", "Sinoboom", "YB003", "AB14EJ", "AB14EJ-11892", "Sinoboom", "2021-08-03", "2024-08-03", "AST-YB003", "บูมไฟฟ้า 14 ม.", 980000, "วิชัย ตั้งตรง", "061-423-2797", "", 1620, "รอการตรวจสอบ"],
    [3, "Boom Lift", "JLG", "YB004", "E450AJ", "E450AJ-44102", "JLG", "2020-01-20", "2023-01-20", "AST-YB004", "เช่าระยะยาว ไทยซัมมิท", 2100000, "ลูกค้า ฝ่ายซ่อมบำรุง", "038-000-111", "อยู่หน้างานระยอง", 3102, "รถอยู่หน้างาน"],
    [1, "Boom Lift", "Haulotte", "YB005", "HA16RTJ", "HA16-7721", "Haulotte", "2018-11-09", "2021-11-09", "AST-YB005", "บูมดีเซลผสม — ใช้ไฟฟ้าในโหมดแพลตฟอร์ม", 1750000, "", "", "รออะไหล่บูม", 4011, "รถเบรคดาวน์ไม่พร้อมใช้"],
    [2, "Boom Lift", "Fronteq", "YB006", "FT14EJ", "FT14-3301", "Fronteq", "2023-02-14", "2026-02-14", "AST-YB006", "จอดสาขาอมตะ", 720000, "", "", "", 540, "พร้อมใช้งาน"],
    [1, "Scissor Lift", "Genie", "YS001", "GS-1932", "GS1932-90811", "Genie", "2020-06-01", "2023-06-01", "AST-YS001", "กรรไกรไฟฟ้า 5.8 ม.", 420000, "", "", "", 1890, "พร้อมใช้งาน"],
    [4, "Scissor Lift", "Sinoboom", "YS002", "0608SE", "0608SE-22109", "Sinoboom", "2022-09-18", "2025-09-18", "AST-YS002", "งานในโรงงาน MELCO", 390000, "MELCO คลัง", "038-111-222", "", 980, "รถอยู่หน้างาน"],
    [5, "Scissor Lift", "JLG", "YS003", "1930ES", "1930ES-55120", "JLG", "2019-04-22", "2022-04-22", "AST-YS003", "คลัง SCG", 455000, "", "", "", 2740, "พร้อมใช้งาน"],
    [1, "Mast Boom", "Genie", "YP001", "GR-20", "GR20-10233", "Genie", "2021-03-08", "2024-03-08", "AST-YP001", "กระเช้าส่วนบุคคล", 310000, "", "", "", 760, "พร้อมใช้งาน"],
    [2, "Mast Boom", "Haulotte", "YP003", "Star 6", "ST6-8844", "Haulotte", "2022-12-01", "2025-12-01", "AST-YP003", "งานในอาคาร", 295000, "", "", "", 410, "รอการตรวจสอบ"],
  ] as const;

  for (const a of assets) {
    run(
      db,
      `INSERT INTO assets (site_id, type, brand, code, model, serial, manufacturer, received_at, warranty_until, asset_number, description, price, contact_name, contact_phone, notes, hour_meter, operational_status, created_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [...a, now],
    );
  }

  const parts = [
    ["YTP-E-0001", "E", "147094GT", "คอนแทคเตอร์ควบคุมปั๊ม", 4, 1850, "PCS.", "Boom Lift / Scissor", "Container A-1", "7 วัน", 2, 4, 12, "อะไหล่ Genie ใช้บ่อย"],
    ["YTP-E-0008", "E", "1254305GT", "ชุดชาร์จแบตเตอรี่ 24V", 2, 6200, "SET.", "ทุกประเภท", "Container A-2", "14 วัน", 1, 2, 4, ""],
    ["YTP-E-0012", "E", "229800GT", "สวิตช์ E-Stop บนกระเช้า", 6, 890, "PCS.", "ทุกประเภท", "ชั้นวาง E", "5 วัน", 3, 6, 15, ""],
    ["YTP-M-0002", "M", "88922GT", "บูชข้อพับบูม", 8, 420, "PCS.", "Boom Lift", "Container B-1", "10 วัน", 4, 8, 20, ""],
    ["YTP-M-0015", "M", "215612GT", "ล้อยางตัน 240x80", 10, 1750, "PCS.", "Scissor Lift", "ลานยาง", "3 วัน", 4, 8, 16, ""],
    ["YTP-H-0032", "H", "66823GT", "ซีลกระบอกไฮดรอลิกบูม", 3, 2400, "SET.", "Boom Lift", "Container H", "21 วัน", 2, 3, 8, "รอของจาก TVH"],
    ["YTP-H-0041", "H", "A31015", "น้ำมันไฮดรอลิก AW46", 80, 95, "L.", "ทุกประเภท", "ถังน้ำมัน", "2 วัน", 40, 60, 200, ""],
    ["YTP-H-0055", "H", "P2060", "สายไฮดรอลิก 3/8 2 ชั้น", 12, 380, "CM.", "Boom Lift", "ม้วนสาย", "7 วัน", 5, 10, 30, ""],
    ["YTP-O-0058", "O", "DECAL-YB", "ชุดสติ๊กเกอร์ความปลอดภัย", 15, 220, "SET.", "Boom Lift", "ลิ้นชัก O", "3 วัน", 5, 10, 30, ""],
    ["YTP-E-0020", "E", "BAT-T105", "แบตเตอรี่ Trojan T-105", 1, 9800, "PCS.", "ทุกประเภท", "ห้องแบต", "21 วัน", 2, 4, 8, "ต่ำกว่าขั้นต่ำ — ต้องสั่ง"],
    ["YTP-M-0028", "M", "PIN-45", "สลักบูม 45 มม.", 0, 1350, "PCS.", "Boom Lift", "Container B-2", "14 วัน", 2, 4, 8, "หมดสต็อก"],
    ["YTP-O-0003", "O", "PORMOR-2", "เอกสาร ปจ.2 สำเนาเล่ม", 40, 15, "PCS.", "ทุกประเภท", "ตู้เอกสาร", "1 วัน", 10, 20, 100, ""],
  ] as const;

  for (const p of parts) {
    run(
      db,
      `INSERT INTO parts (code, category, oem_code, name, qty, unit_cost, unit, used_on, location, lead_time, min_qty, reorder_qty, max_qty, notes)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [...p],
    );
  }

  run(
    db,
    `INSERT INTO vendors (code, company_name, description, contact, phone, address, email, notes) VALUES
      ('V0001', 'Faster Enterprise Co., Ltd.', 'อะไหล่ทั่วไป / ฮาร์ดแวร์', 'คุณจอย', '02-123-4567', 'สมุทรปราการ', 'sales@faster.co.th', ''),
      ('V0028', 'TVH Singapore', 'อะไหล่ OEM Genie / JLG', 'Import desk', '+65 6265 0000', 'Singapore', 'asia@tvh.com', 'สั่งผ่าน importtime'),
      ('J0003', 'Jiangsu 1M Machinery', 'อะไหล่ Sinoboom / Fronteq', 'Mr. Li', '+86 510 000 0000', 'Jiangsu, China', 'sales@1m.cn', ''),
      ('V0012', 'ชลบุรีไฮดรอลิก', 'ซ่อมกระบอก / สายไฮดรอลิก', 'คุณมงคล', '038-555-1212', 'ศรีราชา', '', 'ร้านใกล้สาขาอมตะ')`,
  );

  const requestItems = [
    "โครงสร้างตัวรถ / รอยร้าว รอยเชื่อม",
    "บูม / แขนกระเช้า / โครงกรรไกร",
    "ระบบไฮดรอลิก — น้ำมันรั่วซึม",
    "แบตเตอรี่ สายไฟ ขั้วต่อ",
    "ปุ่มหยุดฉุกเฉิน (E-Stop)",
    "ชุดควบคุมภาคพื้น",
    "ชุดควบคุมบนกระเช้า",
    "ล้อ / ยาง / น็อตล้อ",
    "ราวกั้นกระเช้า และประตู",
    "จุดยึดสายรัดนิรภัย",
    "สวิตช์ลิมิต / ตัดการทำงาน",
    "สัญญาณเตือนขณะลง (descent alarm)",
    "ระดับรถ / ขาค้ำ / เซนเซอร์เอียง",
    "สติ๊กเกอร์ความปลอดภัยและป้ายรับน้ำหนัก",
    "มิเตอร์ชั่วโมง อ่านค่าได้",
    "ทดสอบการทำงานขึ้น-ลง / ยืด-หุบ",
    "ระบบลดระดับฉุกเฉิน",
    "เอกสาร ปจ.2 และคู่มือประจำรถ",
    "สภาพความปลอดภัยโดยรวม พร้อมส่งซ่อม",
  ];
  const mcItems = [
    "ระบุสาเหตุราก (root cause) แล้ว",
    "ดำเนินการแก้ไขตามแผนแล้ว",
    "เปลี่ยนอะไหล่ตามรายการเบิก",
    "ทดสอบฟังก์ชันครบทุกทิศทาง",
    "อุปกรณ์นิรภัยทำงาน (E-Stop, ลิมิต, แตร)",
    "ระบบไฮดรอลิกไม่มีรอยรั่วหลังซ่อม",
    "ระบบไฟฟ้า / ชาร์จ ปกติ",
    "ทำความสะอาดจุดซ่อม",
    "ทดลองขับและทดลองยก",
    "บันทึกชั่วโมงเครื่องหลังซ่อม",
    "แจ้งลูกค้าหรือหัวหน้าหน้างาน",
    "เก็บอะไหล่เก่าเข้าคลังซาก",
    "ตรวจรอยรั่วซ้ำหลังทดลอง 10 นาที",
    "ติดป้ายสถานะรถ (พร้อมใช้ / รอตรวจ)",
    "ถ่ายรูปงานก่อน-หลัง",
    "ผ่านเกณฑ์ส่งมอบ",
  ];
  const pmBoom = [
    "ตรวจระดับน้ำมันไฮดรอลิกและไส้กรอง",
    "อัดจารบีจุดบูชข้อพับทุกจุด",
    "ตรวจสายเคเบิลบูมและรอก",
    "ทดสอบวาล์วฉุกเฉิน",
    "ตรวจแบตเตอรี่ น้ำกลั่น ความถ่วงจำเพาะ",
    "ขันน็อตโครงสร้างตามทอร์ก",
    "ทดสอบระบบควบคุมพื้นและกระเช้า",
    "ตรวจยาง รอยแตก ความดัน/สึก",
    "ล้างถาดแบตและขั้ว",
    "อัปเดตสติ๊กเกอร์ที่ลบเลือน",
    "บันทึกชั่วโมงและลงสมุด PM",
    "ทดลองยกครบสโตรก",
  ];
  const pmScissor = [
    "ตรวจกรรไกร บุช และสลัก",
    "ตรวจกระบอกยกหลัก",
    "ระบบไฮดรอลิกและท่ออ่อน",
    "แบตเตอรี่และชาร์จเจอร์",
    "แพลตฟอร์ม ราว ประตู",
    "ล้อ เบรกจอด",
    "ปุ่มฉุกเฉินทั้งสองจุด",
    "เซนเซอร์โหลด / เอียง",
    "ทำความสะอาดถาดและจุดบานพับ",
    "บันทึกชั่วโมงและลงสมุด PM",
  ];
  const pmMast = [
    "เสากระเช้าและรอกโซ่",
    "ระบบไฟฟ้าและจอยสติ๊ก",
    "ล้อแคสเตอร์ / ล้อขับ",
    "เบรกและสวิตช์ตัดการขับขณะยก",
    "แบตเตอรี่",
    "ราวกั้นและจุดยึดสายรัด",
    "ทดสอบขึ้น-ลงเต็มระยะ",
    "บันทึกชั่วโมงและลงสมุด PM",
  ];

  function insertTemplate(
    name: string,
    liftType: string | null,
    kind: string,
    items: string[],
  ) {
    const res = run(
      db,
      `INSERT INTO checklist_templates (name, lift_type, kind) VALUES (?, ?, ?)`,
      [name, liftType, kind],
    );
    const id = Number(res.lastInsertRowid);
    items.forEach((label, i) => {
      run(
        db,
        `INSERT INTO checklist_items (template_id, code, label, sort_order) VALUES (?, ?, ?, ?)`,
        [id, `I${String(i + 1).padStart(2, "0")}`, label, i + 1],
      );
    });
    return id;
  }

  const tplRequest = insertTemplate("ตรวจเช็คก่อนแจ้งซ่อม", null, "request", requestItems);
  const tplMc = insertTemplate("ตรวจหลังซ่อม (ใบงานซ่อม)", null, "mc", mcItems);
  const tplPmBoom = insertTemplate("PM รายเดือน — Boom Lift", "Boom Lift", "pm", pmBoom);
  const tplPmScissor = insertTemplate("PM รายเดือน — Scissor Lift", "Scissor Lift", "pm", pmScissor);
  const tplPmMast = insertTemplate("PM รายเดือน — Mast Boom", "Mast Boom", "pm", pmMast);

  const today = new Date();
  const isoDay = (offset: number) => {
    const d = new Date(today);
    d.setDate(d.getDate() + offset);
    return d.toISOString().slice(0, 10);
  };

  const pmRows = [
    [1, "PM รายเดือน YB002", 30, isoDay(-5), isoDay(25), tplPmBoom],
    [2, "PM รายเดือน YB003", 30, isoDay(-40), isoDay(-10), tplPmBoom],
    [3, "PM รายเดือน YB004", 30, isoDay(-20), isoDay(10), tplPmBoom],
    [4, "PM รายเดือน YB005", 30, isoDay(-50), isoDay(-20), tplPmBoom],
    [5, "PM รายเดือน YB006", 30, isoDay(-8), isoDay(22), tplPmBoom],
    [6, "PM รายเดือน YS001", 30, isoDay(-12), isoDay(18), tplPmScissor],
    [7, "PM รายเดือน YS002", 30, isoDay(-33), isoDay(-3), tplPmScissor],
    [8, "PM รายเดือน YS003", 30, isoDay(-2), isoDay(28), tplPmScissor],
    [9, "PM รายเดือน YP001", 30, isoDay(-15), isoDay(15), tplPmMast],
    [10, "PM รายเดือน YP003", 30, isoDay(-38), isoDay(-8), tplPmMast],
  ] as const;

  for (const p of pmRows) {
    run(
      db,
      `INSERT INTO pm_schedules (asset_id, title, frequency_days, last_done_at, next_due_at, checklist_template_id, active, notes)
       VALUES (?, ?, ?, ?, ?, ?, 1, 'รอบตรวจประจำเดือนตามแผนอู่')`,
      [...p],
    );
  }

  run(db, `INSERT INTO sequences (name, value) VALUES ('JR', 4), ('MC', 2)`);

  function insertInspection(
    templateId: number,
    jrId: number | null,
    woId: number | null,
    results: Array<"pass" | "fail" | "na">,
    inspectorId: number,
    completed: boolean,
  ) {
    const res = run(
      db,
      `INSERT INTO inspections (template_id, job_request_id, work_order_id, inspector_id, completed_at, created_at)
       VALUES (?, ?, ?, ?, ?, ?)`,
      [templateId, jrId, woId, inspectorId, completed ? now : null, now],
    );
    const inspectionId = Number(res.lastInsertRowid);
    const items = db
      .prepare(`SELECT id FROM checklist_items WHERE template_id = ? ORDER BY sort_order`)
      .all(templateId) as Array<{ id: number }>;
    items.forEach((item, i) => {
      run(
        db,
        `INSERT INTO checklist_results (inspection_id, item_id, result, notes) VALUES (?, ?, ?, '')`,
        [inspectionId, item.id, results[i] ?? "pass"],
      );
    });
  }

  const jr1 = run(
    db,
    `INSERT INTO job_requests (number, asset_id, site_id, job_type, status, internal_status, result, priority, symptom, requester_id, requester_phone, hour_meter, location_note, signature_name, signed_at, work_order_id, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      `JR${year}-0001`,
      4,
      1,
      "BD",
      "อยู่ระหว่างดำเนินการซ่อม",
      "Assign",
      "InProcess",
      "ฉุกเฉิน",
      "บูมยกไม่สุด มีเสียงลมในระบบ น้ำมันไฮดรอลิกรั่วที่กระบอกหลัก รถจอดอู่หนองใหญ่",
      6,
      "033-000-954",
      4011,
      "อู่หนองใหญ่ ช่องซ่อม 2",
      "กัญญา แสงทอง",
      now,
      null,
      isoDay(-2) + "T08:15:00.000Z",
      now,
    ],
  );
  const jr1Id = Number(jr1.lastInsertRowid);

  const wo1 = run(
    db,
    `INSERT INTO work_orders (number, job_request_id, asset_id, job_type, status, internal_status, result, team_id, technician_id, special_info, countermeasure, started_at, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      `MC${year}-0001`,
      jr1Id,
      4,
      "BD",
      "อยู่ระหว่างดำเนินการซ่อม",
      "Assign",
      "InProcess",
      2,
      4,
      "รอซีลกระบอก YTP-H-0032 จากคลัง — ของเหลือ 3 ชุด",
      "",
      isoDay(-1) + "T09:00:00.000Z",
      isoDay(-2) + "T09:30:00.000Z",
      now,
    ],
  );
  const wo1Id = Number(wo1.lastInsertRowid);
  run(db, `UPDATE job_requests SET work_order_id = ? WHERE id = ?`, [wo1Id, jr1Id]);
  run(
    db,
    `INSERT INTO work_order_parts (work_order_id, part_id, qty, cost_per_unit, total_price) VALUES (?, 6, 1, 2400, 2400)`,
    [wo1Id],
  );
  run(
    db,
    `INSERT INTO part_movements (part_id, kind, qty, work_order_id, note, created_by, created_at) VALUES (6, 'issue', 1, ?, 'เบิกเข้าใบงานซ่อม', 4, ?)`,
    [wo1Id, now],
  );
  run(db, `UPDATE parts SET qty = qty - 1 WHERE id = 6`);

  insertInspection(
    tplRequest,
    jr1Id,
    null,
    [
      "pass",
      "fail",
      "fail",
      "pass",
      "pass",
      "pass",
      "pass",
      "pass",
      "pass",
      "pass",
      "pass",
      "pass",
      "pass",
      "na",
      "pass",
      "fail",
      "pass",
      "pass",
      "fail",
    ],
    6,
    true,
  );
  insertInspection(
    tplMc,
    null,
    wo1Id,
    Array(16).fill("") as Array<"pass">,
    4,
    false,
  );

  const jr2 = run(
    db,
    `INSERT INTO job_requests (number, asset_id, site_id, job_type, status, internal_status, result, priority, symptom, requester_id, requester_phone, hour_meter, location_note, signature_name, signed_at, work_order_id, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      `JR${year}-0002`,
      2,
      1,
      "CM",
      "รอมอบหมายทีมซ่อม",
      "Wait",
      "InProcess",
      "ด่วน",
      "จอยสติ๊กกระเช้าสั่งเลี้ยวซ้ายไม่ตอบสนอง กด E-Stop แล้วรีเซ็ตแล้วยังไม่กลับ รถจอดรอตรวจที่อู่",
      6,
      "033-000-954",
      1620,
      "อู่หนองใหญ่ ช่องรอตรวจ",
      "กัญญา แสงทอง",
      now,
      null,
      isoDay(0) + "T07:40:00.000Z",
      now,
    ],
  );
  const jr2Id = Number(jr2.lastInsertRowid);
  insertInspection(
    tplRequest,
    jr2Id,
    null,
    [
      "pass",
      "pass",
      "pass",
      "pass",
      "pass",
      "pass",
      "fail",
      "pass",
      "pass",
      "pass",
      "pass",
      "pass",
      "pass",
      "pass",
      "pass",
      "fail",
      "pass",
      "pass",
      "fail",
    ],
    6,
    true,
  );

  const jr3 = run(
    db,
    `INSERT INTO job_requests (number, asset_id, site_id, job_type, status, internal_status, result, priority, symptom, requester_id, requester_phone, hour_meter, location_note, signature_name, signed_at, work_order_id, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      `JR${year}-0003`,
      1,
      1,
      "PM",
      "ปิดจบงาน",
      "Closed",
      "Finished",
      "ปกติ",
      "ถึงรอบ PM รายเดือน — ตรวจตามเช็คลิสต์ Boom Lift",
      2,
      "061-423-2797",
      2450,
      "อู่หนองใหญ่",
      "วิชัย ตั้งตรง",
      isoDay(-6) + "T16:00:00.000Z",
      null,
      isoDay(-6) + "T08:00:00.000Z",
      isoDay(-5) + "T15:20:00.000Z",
    ],
  );
  const jr3Id = Number(jr3.lastInsertRowid);
  const wo2 = run(
    db,
    `INSERT INTO work_orders (number, job_request_id, asset_id, job_type, status, internal_status, result, team_id, technician_id, special_info, countermeasure, started_at, completed_at, signature_name, signed_at, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      `MC${year}-0002`,
      jr3Id,
      1,
      "PM",
      "ปิดจบงาน",
      "Closed",
      "Finished",
      2,
      4,
      "PM ตามแผน 30 วัน",
      "อัดจารบีครบ จุด เปลี่ยนไส้กรองไฮดรอลิก เติม AW46 2 ลิตร ทดสอบยกครบสโตรก ปกติ",
      isoDay(-6) + "T09:00:00.000Z",
      isoDay(-5) + "T15:00:00.000Z",
      "สมชาย ใจดี",
      isoDay(-5) + "T15:10:00.000Z",
      isoDay(-6) + "T08:20:00.000Z",
      isoDay(-5) + "T15:20:00.000Z",
    ],
  );
  const wo2Id = Number(wo2.lastInsertRowid);
  run(db, `UPDATE job_requests SET work_order_id = ? WHERE id = ?`, [wo2Id, jr3Id]);
  run(
    db,
    `INSERT INTO work_order_parts (work_order_id, part_id, qty, cost_per_unit, total_price) VALUES (?, 7, 2, 95, 190)`,
    [wo2Id],
  );
  insertInspection(
    tplRequest,
    jr3Id,
    null,
    Array(19).fill("pass") as Array<"pass">,
    2,
    true,
  );
  insertInspection(tplMc, null, wo2Id, Array(16).fill("pass") as Array<"pass">, 4, true);

  const jr4 = run(
    db,
    `INSERT INTO job_requests (number, asset_id, site_id, job_type, status, internal_status, result, priority, symptom, requester_id, requester_phone, hour_meter, location_note, created_at, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [
      `JR${year}-0004`,
      3,
      3,
      "SV",
      "รอมอบหมายทีมซ่อม",
      "New",
      "InProcess",
      "ปกติ",
      "ลูกค้าขอช่างเข้าหน้างานตรวจเสียงดังที่มอเตอร์ขับ รถยังใช้งานได้ อยู่ไทยซัมมิท ปลวกแดง",
      6,
      "033-000-954",
      3102,
      "หน้างาน บจก.ไทยซัมมิท โฮทไฮน์",
      isoDay(0) + "T06:55:00.000Z",
      now,
    ],
  );
  const jr4Id = Number(jr4.lastInsertRowid);
  insertInspection(
    tplRequest,
    jr4Id,
    null,
    Array(19).fill("") as Array<"pass">,
    6,
    false,
  );

  run(
    db,
    `INSERT INTO asset_status_logs (asset_id, status, branch, details, start_at, reported_by, created_at) VALUES
      (1, 'พร้อมใช้งาน', 'สาขา หนองใหญ่ ชลบุรี', 'หลังปิดงาน PM', ?, 4, ?),
      (4, 'รถเบรคดาวน์ไม่พร้อมใช้', 'สาขา หนองใหญ่ ชลบุรี', 'บูมไม่สุด รอซีลกระบอก', ?, 4, ?),
      (3, 'รถอยู่หน้างาน', 'สาขา หนองใหญ่ ชลบุรี', 'เช่าไทยซัมมิท', ?, 6, ?),
      (7, 'รถอยู่หน้างาน', 'สาขา อมตะนคร ชลบุรี', 'MELCO', ?, 6, ?)`,
    [isoDay(-5), now, isoDay(-2), now, isoDay(-14), now, isoDay(-7), now],
  );

  run(
    db,
    `INSERT INTO rental_jobs (asset_id, job_no, customer_name, job_site, start_date, end_date, created_by, created_at) VALUES
      (3, 'SV-${year}-014', 'บจก.ไทยซัมมิท โฮทไฮน์', 'ปลวกแดง จ.ระยอง', ?, ?, 6, ?),
      (7, 'SV-${year}-021', 'บจก.มิตซูบิชิ อีเล็คทริค', 'อมตะซิตี้ จ.ชลบุรี', ?, ?, 6, ?)`,
    [isoDay(-14), isoDay(16), now, isoDay(-7), isoDay(23), now],
  );
}
