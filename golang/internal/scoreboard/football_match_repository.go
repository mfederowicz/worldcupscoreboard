package scoreboard

// FootballMatchRepository keeps the matches of the score board. It is the
// part to replace when the matches should live somewhere else than in memory.
type FootballMatchRepository interface {
	// Save adds the match, or replaces the one with the same id.
	Save(match *FootballMatch)
	// Remove deletes the match with the given id; an unknown id is ignored.
	Remove(matchID UUID)
	// Find returns the match and true, or nil and false for an unknown id.
	Find(matchID UUID) (*FootballMatch, bool)
	// All returns every match, in the order they were first saved.
	All() []*FootballMatch
}
