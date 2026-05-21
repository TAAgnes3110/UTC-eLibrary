#!/usr/bin/env python3
"""Di chuyển docs/diagrams → usecase | activity | uml (mmd + puml)."""

from __future__ import annotations

import shutil
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
DIAG = ROOT / "docs" / "diagrams"
MERMAID = DIAG / "mermaid"

USECASE = DIAG / "usecase"
ACTIVITY = DIAG / "activity"
UML = DIAG / "uml"

# Puml admin lỗi thời (đã thay bằng mermaid mới)
OBSOLETE_PUML = {
    "admin/14-the-thu-vien.puml",
    "admin/15-nop-do-an.puml",
}


def move_file(src: Path, dst: Path) -> None:
    if not src.is_file():
        return
    dst.parent.mkdir(parents=True, exist_ok=True)
    if dst.exists():
        dst.unlink()
    shutil.move(str(src), str(dst))
    print(f"  {src.relative_to(DIAG)} -> {dst.relative_to(DIAG)}")


def migrate_mermaid() -> None:
    if not MERMAID.is_dir():
        return

    uc_map = [
        ("01-usecase-tong-quat.mmd", USECASE / "01-usecase-tong-quat.mmd"),
        ("ban-doc", USECASE / "ban-doc"),
        ("admin", USECASE / "admin"),
        ("he-thong", USECASE / "he-thong"),
    ]
    for name, dest in uc_map:
        src = MERMAID / name
        if src.is_file():
            move_file(src, dest)
        elif src.is_dir():
            for f in src.rglob("*.mmd"):
                rel = f.relative_to(src)
                move_file(f, dest / rel)

    act_src = MERMAID / "activity"
    if act_src.is_dir():
        for f in act_src.glob("*.mmd"):
            move_file(f, ACTIVITY / f.name)

    for sub in ("sequence", "state", "erd", "class", "kien-truc"):
        src_dir = MERMAID / sub
        if src_dir.is_dir():
            for f in src_dir.glob("*.mmd"):
                move_file(f, UML / sub / f.name)


def migrate_legacy_puml() -> None:
    move_file(DIAG / "01-usecase-tong-quat.puml", USECASE / "01-usecase-tong-quat.puml")

    for sub in ("ban-doc", "admin", "he-thong"):
        src_dir = DIAG / sub
        if not src_dir.is_dir():
            continue
        for f in src_dir.glob("*.puml"):
            rel = f"{sub}/{f.name}"
            if rel.replace("\\", "/") in OBSOLETE_PUML:
                f.unlink()
                print(f"  delete obsolete {rel}")
                continue
            move_file(f, USECASE / sub / f.name)

    for sub in ("sequence", "state", "erd", "class", "kien-truc"):
        src_dir = DIAG / sub
        if src_dir.is_dir():
            for f in src_dir.glob("*.puml"):
                move_file(f, UML / sub / f.name)


def cleanup_empty() -> None:
    for path in sorted(DIAG.rglob("*"), reverse=True):
        if path.is_dir() and path not in (USECASE, ACTIVITY, UML, DIAG):
            try:
                path.rmdir()
            except OSError:
                pass
    if MERMAID.exists():
        shutil.rmtree(MERMAID, ignore_errors=True)
    preview = USECASE / "ban-doc" / "03-dang-nhap-dang-ky.preview.md"
    if preview.is_file():
        preview.unlink()


def main() -> None:
    print("Migrate mermaid...")
    migrate_mermaid()
    print("Migrate plantuml...")
    migrate_legacy_puml()
    print("Cleanup...")
    cleanup_empty()
    print("Done:", USECASE, ACTIVITY, UML)


if __name__ == "__main__":
    main()
