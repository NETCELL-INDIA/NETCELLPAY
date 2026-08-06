from pathlib import Path

p = Path(__file__).resolve().parents[1] / 'resources/views/admin/users/list.blade.php'
text = p.read_text(encoding='utf-8')
marker_start = '        return;\n\n        $.ajax({'
marker_end = '    $("#edit_details_form").submit(function(e) {'
start = text.find(marker_start)
end = text.find(marker_end)
if start == -1 or end == -1:
    raise SystemExit(f'markers not found: {start}, {end}')
text = text[:start] + text[end:]
p.write_text(text, encoding='utf-8')
print('ok')
