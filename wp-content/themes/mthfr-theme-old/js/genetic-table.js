// // Save this as genetic-table.js in your theme's assets/js folder
// jQuery(document).ready(function ($) {
//     // Toggle pathway content
//     $('.pathway-header').on('click', function () {
//         const pathwayType = $(this).data('pathway');
//         $(this).toggleClass('active');
//         $('.pathway-content[data-pathway="' + pathwayType + '"]').toggleClass('active');
//     });

//     // Toggle info section
//     $('.snp-icon').on('click', function (e) {
//         e.stopPropagation();
//         const snpId = $(this).data('snp-id');
//         $('#info-' + snpId).toggle();
//     });

    
// });


// // tag-input.js
// $(document).ready(function () {
//     const availableTags = ['cat', 'dog', 'dogfish', 'fish']; // Add your tags here
//     let selectedTags = [];

//     const $input = $('#tag-input');
//     const $tagContainer = $('.tag-container');
//     const $selectedTags = $('.selected-tags');

//     $input.autocomplete({
//         source: function (request, response) {
//             const term = request.term.toLowerCase();
//             const filteredTags = availableTags.filter(tag =>
//                 tag.toLowerCase().includes(term) &&
//                 !selectedTags.includes(tag)
//             );
//             response(filteredTags);
//         },
//         minLength: 1,
//         select: function (event, ui) {
//             addTag(ui.item.value);
//             event.preventDefault();
//             $(this).val('');
//             return false;
//         }
//     });

//     function addTag(tag) {
//         if (!selectedTags.includes(tag)) {
//             selectedTags.push(tag);
//             const $tag = $(`
//                 <span class="tag">
//                     ${tag}
//                     <span class="tag-remove">×</span>
//                 </span>
//             `);
//             $selectedTags.append($tag);
//             $input.val('');
//         }
//     }

//     $(document).on('click', '.tag-remove', function () {
//         const $tag = $(this).parent();
//         const tagText = $tag.text().slice(0, -1); // Remove × character
//         selectedTags = selectedTags.filter(tag => tag !== tagText);
//         $tag.remove();
//     });
// });


