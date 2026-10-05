package scoreboard

// ScoreBoardError is the type of every error the score board returns.
// Check for one of them with errors.Is, or for any of them with errors.As.
type ScoreBoardError string

func (e ScoreBoardError) Error() string {
	return string(e)
}

const (
	// ErrInvalidFootballMatch means the given teams, id or score are not allowed.
	ErrInvalidFootballMatch ScoreBoardError = "invalid football match"
	// ErrInvalidFootballMatchState means the action does not fit the state of
	// the match, for example a score update after the match was finished.
	ErrInvalidFootballMatchState ScoreBoardError = "invalid football match state"
)
