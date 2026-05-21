#!/usr/bin/env python3
"""Khôi phục file PlantUML — một header, nội dung từ dòng title/actor trở đi."""

from __future__ import annotations

import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
DIAGRAMS = ROOT / "docs" / "diagrams"

CONTENT_START = (
    "title ",
    "actor ",
    "rectangle ",
    "participant ",
    "|",
    "entity ",
    "class ",
    "node ",
    "cloud ",
    "queue ",
    "database ",
    "[*]",
    "state ",
    "left to right",
)


def extract_core(text: str) -> str:
    m = re.search(r"@startuml\s*\n(.*)\n@enduml", text, re.DOTALL | re.IGNORECASE)
    if not m:
        raise ValueError("Missing @startuml/@enduml")
    lines = m.group(1).split("\n")
    start = 0
    for i, line in enumerate(lines):
        s = line.strip()
        if s in ("}", "{"):
            continue
        if s.startswith("!theme") or s.startswith("skinparam"):
            continue
        if s.startswith(CONTENT_START) or s == "start":
            start = i
            break
    core = "\n".join(lines[start:]).strip()
    core = core.replace("—", "-")
    core = core.replace("<<include>>", "«include»")
    core = core.replace("<<extend>>", "«extend»")
    return core


def detect_kind(core: str) -> str:
    if re.search(r"^\|", core, re.M) or "\nstart\n" in f"\n{core}\n":
        return "activity"
    if "entity " in core:
        return "erd"
    if "participant " in core:
        return "sequence"
    if "[*]" in core or re.search(r"^state ", core, re.M):
        return "state"
    if re.search(r"^class ", core, re.M):
        return "class"
    if "node " in core or "cloud " in core:
        return "arch"
    return "usecase"


USECASE_HDR = """@startuml
!theme plain
skinparam shadowing false
skinparam defaultFontName Arial
skinparam usecaseBackgroundColor #FEFECE
skinparam usecaseBorderColor #333333
skinparam actorBorderColor #333333

"""

OTHER_HDR = """@startuml
!theme plain
skinparam shadowing false
skinparam defaultFontName Arial

"""


def repair_file(path: Path) -> None:
    core = extract_core(path.read_text(encoding="utf-8"))
    hdr = USECASE_HDR if detect_kind(core) == "usecase" else OTHER_HDR
    path.write_text(f"{hdr}{core}\n@enduml\n", encoding="utf-8", newline="\n")


def main() -> None:
    for f in sorted(DIAGRAMS.rglob("*.puml")):
        repair_file(f)
        print(f"OK {f.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
