import os
import re

def update_notif_icon_regex(path):
    if not os.path.exists(path):
        print(f"Skipping {path}, not found.")
        return
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # Target the notification bell container with a multi-line regex
    pattern = r'<div class="position-relative p-2 rounded-circle"\s+style="cursor:pointer;background:var\(--bg-card\);border:1px solid var\(--border\);"\s+data-bs-toggle="dropdown">'
    new_replacement = '<div class="position-relative" style="cursor:pointer" data-bs-toggle="dropdown">'
    
    new_content = re.sub(pattern, new_replacement, content, flags=re.MULTILINE)
    
    if new_content != content:
        with open(path, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {path}")
    else:
        # Try one more variation without the space before style or after it
        print(f"No match for {path}, trying variation.")
        pattern2 = r'class="position-relative p-2 rounded-circle"\s+style="cursor:pointer;background:var\(--bg-card\);border:1px solid var\(--border\);"'
        new_replacement2 = 'class="position-relative" style="cursor:pointer"'
        new_content2 = re.sub(pattern2, new_replacement2, content, flags=re.MULTILINE)
        if new_content2 != content:
            with open(path, 'w', encoding='utf-8') as f:
                f.write(new_content2)
            print(f"Updated {path} (variation)")
        else:
            print(f"Failed to update {path}")

update_notif_icon_regex(r'C:\xampp\htdocs\SCC_Frontend\faculty\my_submissions.php')
update_notif_icon_regex(r'C:\xampp\htdocs\SCC_Frontend\dept_head\my_submissions.php')
update_notif_icon_regex(r'C:\xampp\htdocs\SCC_Frontend\dept_head\syllabus_review.php')
