-- The Analytics Lua library gets data for the Analytics Lua module
local analytics = {}
local php

function analytics.setupInterface( options )
	-- Remove setup function
	analytics.setupInterface = nil

	-- Copy the PHP callbacks to a local variable, and remove the global
	php = mw_interface
	mw_interface = nil

	-- Install into the mw global
	mw = mw or {}
	mw.ext = mw.ext or {}
	mw.ext.analytics = analytics

	-- Indicate that we're loaded
	package.loaded['mw.ext.analytics'] = analytics
end

function analytics.getViewsData( page )
	return php.getViewsData( page )
end

function analytics.getEditsData( page )
	return php.getEditsData( page )
end

return analytics
