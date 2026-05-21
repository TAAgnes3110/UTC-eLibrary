#!/usr/bin/env python3
"""Chuẩn hóa file PlantUML — chạy repair_plantuml_diagrams.py."""

import subprocess
import sys
from pathlib import Path

if __name__ == "__main__":
    script = Path(__file__).parent / "repair_plantuml_diagrams.py"
    raise SystemExit(subprocess.call([sys.executable, str(script)]))
