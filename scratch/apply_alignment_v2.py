import re

def update_file_regex(path, pattern, replacement):
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    new_content = re.sub(pattern, replacement, content, flags=re.MULTILINE)
    if new_content != content:
        with open(path, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {path}")
    else:
        print(f"No match in {path}")

# Regex to match the flex container for tabs and search
pattern = r'<!-- Tabs \+ Search (?:Bar )?Row -->\s*<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 animate-in"\s*style="--animation-order:3">'
replacement = r'<!-- Tabs + Search Row -->\n        <div class="scc-tab-search-wrapper animate-in" style="--animation-order:3">'

update_file_regex(r'C:\xampp\htdocs\SCC_Frontend\dept_head\my_submissions.php', pattern, replacement)
