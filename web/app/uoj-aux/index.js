const express = require('express')
const uoj_marked = require('../../public/js/uoj-marked.js')
const slide_marked = require('../../public/js/marked.js')
const bodyParser = require('body-parser')

slide_marked.setOptions({
	getLangClass: function(lang) {
		lang = lang.toLowerCase();
		switch (lang) {
			case 'c': return 'c'
			case 'c++': return 'cpp'
			case 'pascal': return 'pascal'
			default: return lang
		}
	},
	getElementClass: function(tok) {
		switch (tok.type) {
			case 'list_item_start':
				return 'fragment'
			case 'loose_item_start':
				return 'fragment'
			default:
				return null
		}
	}
})

var app = express()
app.use(bodyParser.text({limit: '50mb'}))

app.post('/render-md/uoj', function (req, res) {
	req.setTimeout(25000, function() {
		res.send('编译时间超出限制，问问管理员怎么回事？')
	});
	try {
		out = uoj_marked(req.body)
	} catch (e) {
		out = '编译失败，请发给管理员看看！'
	}
	res.send(out)
})

app.post('/render-md/slide', function (req, res) {
	req.setTimeout(25000, function() {
		res.send('编译时间超出限制，问问管理员怎么回事？')
	});
	try {
		out = slide_marked(req.body)
	} catch (e) {
		out = '编译失败，请发给管理员看看！'
	}
	res.send(out)
})

// 7513 is just a random number
const server = app.listen('7513', () => {
	console.log('Server is listening on port 7513')
})