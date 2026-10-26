function syncEmptyTdSpanHeights() {
  const container = document.getElementById('dhbw_part_cert_res');
  if (!container) return;

  const spans = container.querySelectorAll('span.td-span');

  spans.forEach((span) => {
    // Reset first so recalculations (e.g. on resize) start clean
    span.style.height = '';

    const hasContent = span.textContent.trim() !== '' || span.children.length > 0;
    if (hasContent) return;

    const td = span.closest('td');
    if (!td) return;

    // Find the next td that has a .td-span with content
    let nextTd = td.nextElementSibling;
    while (nextTd) {
      const nextSpan = nextTd.querySelector('.td-span');
      if (nextSpan && nextSpan.textContent.trim() !== '') {
        const { height } = nextSpan.getBoundingClientRect();
        span.style.display = 'block';
        span.style.width = '100%';
        span.style.height = `${height}px`;
        break;
      }
      nextTd = nextTd.nextElementSibling;
    }
  });
}

document.addEventListener('DOMContentLoaded', syncEmptyTdSpanHeights);

// Recalculate when the layout changes
window.addEventListener('resize', syncEmptyTdSpanHeights);