import { apiBase } from "./api";

// penanganan gambar dri becekend
export function toPublicUrl(rawPath) {
  if (!rawPath || typeof rawPath !== "string") return null;
  const normalized = rawPath.startsWith("/") ? rawPath.slice(1) : rawPath;
  return `${apiBase}storage/${normalized}`;
}
