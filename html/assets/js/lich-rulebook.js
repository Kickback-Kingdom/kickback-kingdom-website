// Javascript for the L.I.C.H. rulebook page

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('rulebook-search');
    const container = document.getElementById('rulebook-container');

    let rulebookData = null;

    // Fetch the rulebook JSON and render it once loaded
    fetch('/assets/data/lich-rulebook.json')
        .then((resp) => resp.json())
        .then((data) => {
            rulebookData = data;
            renderSections(data.sections || []);
        })
        .catch((err) => {
            console.error('Failed to load rulebook:', err);
        });

    // Render sections and pages into the container
    function renderSections(sections) {
        container.innerHTML = '';

        sections.forEach((section) => {
            const sectionEl = document.createElement('section');

            if (section.title) {
                const h2 = document.createElement('h2');
                h2.textContent = section.title;

                if (section.icon) {
                    const icon = document.createElement('img');
                    icon.src = section.icon;
                    icon.alt = '';
                    icon.classList.add('section-icon');
                    h2.prepend(icon);
                }

                sectionEl.appendChild(h2);
            }

            (section.pages || []).forEach((page) => {
                const pageEl = document.createElement('article');
                pageEl.classList.add('rulebook-page');

                const pageId = `${section.slug || section.id}-${page.slug || page.id}`;
                const h3 = document.createElement('h3');
                h3.innerHTML = `<a id="${pageId}" href="#${pageId}">${page.title}</a>`;
                pageEl.appendChild(h3);

                const tagSet = new Set(page.tags || []);

                if (page.body) {
                    page.body.forEach((p) => {
                        const para = document.createElement('p');
                        para.innerHTML = p;
                        pageEl.appendChild(para);
                    });
                }

                if (page.rules) {
                    const list = document.createElement('ol');
                    page.rules.forEach((rule) => {
                        const li = document.createElement('li');
                        li.innerHTML = `<strong>${rule.rid}</strong> ${rule.text}`;
                        list.appendChild(li);
                        (rule.tags || []).forEach((t) => tagSet.add(t));
                    });
                    pageEl.appendChild(list);
                }

                if (page.subsections) {
                    page.subsections.forEach((sub) => {
                        const subTitle = document.createElement('h4');
                        subTitle.textContent = sub.title;
                        pageEl.appendChild(subTitle);

                        const subList = document.createElement('ol');
                        sub.rules.forEach((rule) => {
                            const li = document.createElement('li');
                            li.innerHTML = `<strong>${rule.rid}</strong> ${rule.text}`;
                            subList.appendChild(li);
                            (rule.tags || []).forEach((t) => tagSet.add(t));
                        });
                        pageEl.appendChild(subList);
                    });
                }

                pageEl.dataset.tags = Array.from(tagSet).join(' ').toLowerCase();
                pageEl.dataset.original = pageEl.innerHTML;
                sectionEl.appendChild(pageEl);
            });

            container.appendChild(sectionEl);
        });
    }

    // Filter pages based on search input
    searchInput.addEventListener('keyup', (e) => {
        const term = e.target.value.toLowerCase();
        const pages = container.querySelectorAll('.rulebook-page');

        pages.forEach((page) => {
            // Reset page content to original to remove previous highlights
            page.innerHTML = page.dataset.original;

            const text = page.textContent.toLowerCase();
            const tags = page.dataset.tags || '';

            if (text.includes(term) || tags.includes(term)) {
                page.style.display = '';

                if (term) {
                    const regex = new RegExp(`(${term})`, 'gi');
                    page.innerHTML = page.innerHTML.replace(regex, '<span class="search-highlight">$1</span>');
                }
            } else {
                page.style.display = 'none';
            }
        });
    });
});
