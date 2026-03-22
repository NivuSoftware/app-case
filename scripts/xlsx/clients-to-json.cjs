const XLSX = require("xlsx");

const inputPath = process.argv[2];

if (!inputPath) {
  console.error("Uso: node clients-to-json.cjs <inputPath>");
  process.exit(1);
}

function normalizeHeader(value) {
  return String(value || "")
    .trim()
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/\s+/g, "_");
}

const wb = XLSX.readFile(inputPath, { cellDates: false });
const sheetName = wb.SheetNames[0];

if (!sheetName) {
  console.log("[]");
  process.exit(0);
}

const ws = wb.Sheets[sheetName];
const rawRows = XLSX.utils.sheet_to_json(ws, { defval: "", raw: false });

const rows = rawRows.map((row) => {
  const normalized = {};

  for (const [key, val] of Object.entries(row)) {
    normalized[normalizeHeader(key)] = val;
  }

  return {
    identificacion: normalized.identificacion || "",
    business: normalized.business || "",
    tipo: normalized.tipo || "",
    telefono: normalized.telefono || "",
    ciudad: normalized.ciudad || "",
    email: normalized.email || "",
  };
});

process.stdout.write(JSON.stringify(rows));
