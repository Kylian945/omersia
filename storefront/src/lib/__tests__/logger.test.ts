describe('logger', () => {
  const originalNodeEnv = process.env.NODE_ENV;

  const loadLogger = () => {
    jest.resetModules();
    // eslint-disable-next-line @typescript-eslint/no-var-requires
    return require('../logger').logger as {
      error: (message: string, data?: unknown) => void;
      warn: (message: string, data?: unknown) => void;
      info: (message: string, data?: unknown) => void;
      debug: (message: string, data?: unknown) => void;
    };
  };

  afterEach(() => {
    process.env.NODE_ENV = originalNodeEnv;
    jest.restoreAllMocks();
  });

  it('logs errors in test environment', () => {
    process.env.NODE_ENV = 'test';
    const logger = loadLogger();
    const spy = jest.spyOn(console, 'error').mockImplementation(() => {});

    logger.error('Something failed', { code: 500 });

    expect(spy).toHaveBeenCalledWith('[ERROR] Something failed', { code: 500 });
  });

  it('logs warnings in test environment', () => {
    process.env.NODE_ENV = 'test';
    const logger = loadLogger();
    const spy = jest.spyOn(console, 'warn').mockImplementation(() => {});

    logger.warn('Deprecated');

    expect(spy).toHaveBeenCalledWith('[WARN] Deprecated', '');
  });

  it('does not log info outside development', () => {
    process.env.NODE_ENV = 'test';
    const logger = loadLogger();
    const spy = jest.spyOn(console, 'log').mockImplementation(() => {});

    logger.info('Info message');

    expect(spy).not.toHaveBeenCalled();
  });
});
