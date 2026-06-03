import os

def update_file(path, old_pattern, new_pattern):
    if not os.path.exists(path):
        print(f"Skipping {path}, not found.")
        return
    with open(path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if old_pattern in content:
        new_content = content.replace(old_pattern, new_pattern)
        with open(path, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {path}")
    else:
        print(f"Pattern not found in {path}")

# Pattern for Faculty Submissions and Dean Submissions
old_submissions = """<!-- Tabs + Search Row -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 animate-in" style="--animation-order:3">
            <div class="scc-tabs-container" id="submissionTabs" role="tablist">"""

new_submissions = """<!-- Tabs + Search Row -->
        <div class="scc-tab-search-wrapper animate-in" style="--animation-order:3">
            <div class="scc-tabs-container" id="submissionTabs" role="tablist">"""

old_search_container = """<div class="position-relative mb-4" style="width:100%;max-width:300px;">"""
new_search_container = """<div class="position-relative search-container" style="width:100%;max-width:300px;">"""

# Pattern for Syllabus Review
old_review = """<!-- Tabs + Search Row -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end gap-3 animate-in" style="--animation-order:3">
            <div class="scc-tabs-container" id="reviewTabs" role="tablist">"""

new_review = """<!-- Tabs + Search Row -->
        <div class="scc-tab-search-wrapper animate-in" style="--animation-order:3">
            <div class="scc-tabs-container" id="reviewTabs" role="tablist">"""

# Apply changes
update_file(r'C:\xampp\htdocs\SCC_Frontend\faculty\my_submissions.php', old_submissions, new_submissions)
update_file(r'C:\xampp\htdocs\SCC_Frontend\dept_head\my_submissions.php', old_submissions, new_submissions)
update_file(r'C:\xampp\htdocs\SCC_Frontend\dept_head\syllabus_review.php', old_review, new_review)

# Apply search container fix to all
update_file(r'C:\xampp\htdocs\SCC_Frontend\faculty\my_submissions.php', old_search_container, new_search_container)
update_file(r'C:\xampp\htdocs\SCC_Frontend\dept_head\my_submissions.php', old_search_container, new_search_container)
update_file(r'C:\xampp\htdocs\SCC_Frontend\dept_head\syllabus_review.php', old_search_container, new_search_container)
