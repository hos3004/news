(function(wp){
  const el = wp.element.createElement;
  const InspectorControls = wp.blockEditor.InspectorControls;
  const useBlockProps = wp.blockEditor.useBlockProps;
  const PanelBody = wp.components.PanelBody;
  const TextControl = wp.components.TextControl;
  const SelectControl = wp.components.SelectControl;
  const RangeControl = wp.components.RangeControl;

  wp.blocks.registerBlockType('nt/news-ticker', {
    edit: function(props){
      const atts = props.attributes;
      const set = props.setAttributes;
      const blockProps = useBlockProps({ className: 'nt-block' });

      return el(wp.element.Fragment, null,
        el(InspectorControls, null,
          el(PanelBody, {title: 'General'},
            el(RangeControl, {label:'Items Count', value: atts.count, onChange: function(v){ set({count:v}); }, min:1, max:20}),
            el(TextControl, {label:'Category Slug', value: atts.category, onChange: function(v){ set({category:v}); }}),
            el(RangeControl, {label:'Speed (ms)', value: atts.speed, onChange: function(v){ set({speed:v}); }, min:100, max:20000}),
            el(SelectControl, {label:'Direction', value: atts.direction, options:[{label:'RTL', value:'rtl'},{label:'LTR', value:'ltr'}], onChange: function(v){ set({direction:v}); }}),
            el(SelectControl, {label:'Mode', value: atts.mode, options:[{label:'Marquee', value:'marquee'},{label:'Typewriter', value:'typewriter'}], onChange: function(v){ set({mode:v}); }}),
            el(SelectControl, {label:'Sticky', value: atts.sticky, options:[{label:'Off', value:'off'},{label:'Sticky First', value:'first'},{label:'Sticky Only', value:'only'}], onChange: function(v){ set({sticky:v}); }})
          )
        ),
        el('div', blockProps, el('p', null, 'Preview: ', atts.mode,' • ',atts.direction,' • ', (atts.category||'all'),' • ', atts.count, ' items • sticky: ', atts.sticky), el('p', null, 'سيتم العرض الحقيقي في الواجهة الأمامية.'))
      );
    },
    save: function(){ return null; } // render via PHP in front-end
  });
})(window.wp);
