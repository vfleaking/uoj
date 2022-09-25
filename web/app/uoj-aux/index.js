const express = require('express')
const uoj_marked = require('../../public/js/uoj-marked.js')
const slide_marked = require('../../public/js/marked.js')
const bodyParser = require('body-parser')

slide_marked.setOptions({
	getLangClass: function(lang) {
		lang = lang.toLowerCase();
		switch (lang) {
			case 'c': return 'c';
			case 'c++': return 'cpp';
			case 'pascal': return 'pascal';
			default: return lang;
		}
	},
	getElementClass: function(tok) {
		switch (tok.type) {
			case 'list_item_start':
				return 'fragment';
			case 'loose_item_start':
				return 'fragment';
			default:
				return null;
		}
	}
})

var app = express()
app.use(bodyParser.text())

app.post('/render-md/uoj', function (req, res) {
	req.setTimeout(2000, function() {
		res.send('编译时间超出限制');
	});
	res.send(uoj_marked(req.body))
})

app.post('/render-md/slide', function (req, res) {
	req.setTimeout(2000, function() {
		res.send('编译时间超出限制');
	});
	res.send(slide_marked(req.body))
})

// 7513 is just a random number
const server = app.listen('7513', () => {
  console.log('Server is listening on port 7513')
})