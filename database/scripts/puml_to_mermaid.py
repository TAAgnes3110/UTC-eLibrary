#!/usr/bin/env python3
"""Sinh file .mmd (Mermaid) từ .puml — xem preview Cursor không lỗi UnknownDiagramError."""

from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
DIAG = ROOT / "docs" / "diagrams"
# uml/ chỉ giữ .puml để vẽ đồ án — không sinh .mmd
SRC_DIRS = (
    DIAG / "usecase",
    DIAG / "activity",
)


def parse_usecase_puml(text: str) -> dict:
    body = re.search(r"@startuml\s*\n(.*)\n@enduml", text, re.DOTALL | re.I)
    if not body:
        return {}
    lines = body.group(1).split("\n")
    title = ""
    actors: list[tuple[str, str]] = []
    system = "He thong"
    usecases: list[tuple[str, str]] = []
    links: list[tuple[str, str, str]] = []  # from, to, style

    in_rect = False
    for line in lines:
        s = line.strip()
        if s.startswith("title "):
            title = s[6:].strip()
        elif s.startswith("actor "):
            m = re.match(r'actor\s+"([^"]+)"\s+as\s+(\w+)', s)
            if m:
                actors.append((m.group(1), m.group(2)))
        elif s.startswith("rectangle "):
            m = re.match(r'rectangle\s+"([^"]+)"', s)
            if m:
                system = m.group(1)
            in_rect = True
        elif s.startswith("usecase "):
            m = re.match(r'usecase\s+"([^"]+)"\s+as\s+(\w+)', s)
            if m:
                usecases.append((m.group(1), m.group(2)))
        elif s == "}":
            in_rect = False
        elif "-->" in s or ".." in s:
            m = re.match(
                r"(\w+)\s+(-+>|\.\.>)\s+(\w+)(?:\s*:\s*(.+))?",
                s,
            )
            if m:
                style = "dotted" if ".." in m.group(2) else "solid"
                label = (m.group(4) or "").strip().replace("«", "").replace("»", "")
                links.append((m.group(1), m.group(3), f"{style}|{label}"))

    return {
        "title": title,
        "actors": actors,
        "system": system,
        "usecases": usecases,
        "links": links,
    }


def usecase_to_mermaid(data: dict) -> str:
    title = data.get("title", "Use case")
    system = data.get("system", "UTC eLibrary").replace('"', "'")
    lines = ["flowchart TB", f"%% {title}", ""]
    for name, aid in data.get("actors", []):
        safe = name.replace('"', "'")
        lines.append(f'  {aid}(("{safe}"))')
    lines.append(f'  subgraph SYS["{system}"]')
    lines.append("    direction TB")
    for name, uid in data.get("usecases", []):
        safe = name.replace('"', "'")
        lines.append(f"    {uid}([{safe}])")
    lines.append("  end")
    lines.append("")
    for fr, to, meta in data.get("links", []):
        parts = meta.split("|", 1)
        style = parts[0]
        label = parts[1] if len(parts) > 1 else ""
        if style == "dotted" and label:
            lines.append(f"  {fr} -.->|{label}| {to}")
        elif style == "dotted":
            lines.append(f"  {fr} -.-> {to}")
        else:
            lines.append(f"  {fr} --> {to}")
    return "\n".join(lines) + "\n"


def parse_activity_puml(text: str) -> str | None:
    body = re.search(r"@startuml\s*\n(.*)\n@enduml", text, re.DOTALL | re.I)
    if not body:
        return None
    raw = body.group(1)
    if not re.search(r"^\|", raw, re.M) and "start" not in raw:
        return None

    title_m = re.search(r"^title\s+(.+)$", raw, re.M)
    title = title_m.group(1).strip() if title_m else "Activity"

    lines = ["flowchart TD", f"%% {title}", ""]
    lane = "He thong"
    node_id = 0

    def nid() -> str:
        nonlocal node_id
        node_id += 1
        return f"n{node_id}"

    for line in raw.split("\n"):
        s = line.strip()
        if s.startswith("title ") or s.startswith("!") or s.startswith("skinparam"):
            continue
        if s.startswith("|") and s.endswith("|"):
            lane = s.strip("|").strip()
            safe = lane.replace('"', "'")
            lines.append(f'  subgraph L_{len(lines)}["{safe}"]')
            lines.append("    direction TB")
            continue
        if s == "start":
            lines.append(f"    {nid()}((Bat dau))")
        elif s == "stop":
            lines.append(f"    {nid()}((Ket thuc))")
            if lines and "subgraph" in lines[-2]:
                lines.append("  end")
        elif s.startswith(":") and s.endswith(";"):
            label = s[1:-1].replace("\\n", " ").replace('"', "'")[:60]
            lines.append(f"    {nid()}[{label}]")
        elif s.startswith("if ") and "then" in s:
            cond = re.sub(r"if\s*\((.+)\)\s*then\s*\((.+)\)", r"\1", s)
            lines.append(f"    {nid()}{{{cond}}}")
        elif s.startswith("else") or s.startswith("elseif"):
            pass
        elif s == "endif":
            pass
    if lines and not lines[-1].strip() == "end":
        if any("subgraph" in x for x in lines):
            lines.append("  end")
    return "\n".join(lines) + "\n"


def convert_file(src: Path) -> Path | None:
    text = src.read_text(encoding="utf-8")
    out = src.with_suffix(".mmd")

    if "usecase " in text or "actor " in text:
        data = parse_usecase_puml(text)
        if not data.get("usecases"):
            return None
        content = usecase_to_mermaid(data)
    else:
        content = parse_activity_puml(text)
        if not content:
            # sequence: giữ flowchart đơn giản
            if "participant " in text:
                content = sequence_to_mermaid(text)
            else:
                return None

    out.parent.mkdir(parents=True, exist_ok=True)
    out.write_text(content, encoding="utf-8", newline="\n")
    return out


def sequence_to_mermaid(text: str) -> str:
    body = re.search(r"@startuml\s*\n(.*)\n@enduml", text, re.DOTALL | re.I)
    if not body:
        return ""
    title_m = re.search(r"^title\s+(.+)$", body.group(1), re.M)
    title = title_m.group(1).strip() if title_m else "Sequence"
    lines = ["sequenceDiagram", f"%% {title}", ""]
    for m in re.finditer(
        r'(participant|actor|database)\s+"([^"]+)"\s+as\s+(\w+)',
        body.group(1),
    ):
        kind, label, alias = m.groups()
        if kind == "actor":
            lines.append(f"  actor {alias} as {label}")
        elif kind == "database":
            lines.append(f"  participant {alias} as {label}")
        else:
            lines.append(f"  participant {alias} as {label}")
    for m in re.finditer(
        r"(\w+)\s+(-+>)\s+(\w+)\s*:\s*(.+)",
        body.group(1),
    ):
        a, _, b, msg = m.groups()
        msg = msg.strip().replace('"', "'")[:50]
        lines.append(f"  {a}->>{b}: {msg}")
    return "\n".join(lines) + "\n"


def main() -> None:
    count = 0
    for base in SRC_DIRS:
        if not base.is_dir():
            continue
        for puml in sorted(base.rglob("*.puml")):
            out = convert_file(puml)
            if out:
                count += 1
                print(f"  {out.relative_to(ROOT)}")
    print(f"Done: {count} file .mmd")


if __name__ == "__main__":
    main()
