function syncEmptyTdSpanHeights() {
  const containers = document.querySelectorAll('.dhbw_part_cert_res');
  if (!containers.length) return;

  containers.forEach((container) => {
    const spans = container.querySelectorAll('span.td-span');

    spans.forEach((span) => {
      span.style.height = '';

      const hasContent = span.textContent.trim() !== '' || span.children.length > 0;
      if (hasContent) return;

      const td = span.closest('td');
      if (!td) return;

      // Find the next/previous td that has a .td-span with content
      let tdWithHeight = td.nextElementSibling;

      if (tdWithHeight == null) {
        tdWithHeight = td.previousElementSibling;
      }

      while (tdWithHeight) {
        const nextSpan = tdWithHeight.querySelector('.td-span');
        if (nextSpan && nextSpan.textContent.trim() !== '') {
          const { height } = nextSpan.getBoundingClientRect();
          span.style.display = 'block';
          span.style.width = '100%';
          span.style.height = `${height}px`;
          break;
        }
        tdWithHeight = tdWithHeight.nextElementSibling;
      }
    });
  });
}

document.addEventListener('DOMContentLoaded', syncEmptyTdSpanHeights);

window.addEventListener('resize', syncEmptyTdSpanHeights);
