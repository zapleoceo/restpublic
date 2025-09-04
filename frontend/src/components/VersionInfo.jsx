import React from 'react';

const VersionInfo = () => {
  const buildTime = new Date().toISOString();
  const version = '1.0.20'; // Из package.json
  
  return (
    <div style={{
      position: 'fixed',
      top: '10px',
      right: '10px',
      backgroundColor: 'rgba(0, 0, 0, 0.8)',
      color: 'white',
      padding: '10px',
      borderRadius: '5px',
      fontSize: '12px',
      fontFamily: 'monospace',
      zIndex: '10002',
      border: '2px solid #00ff00'
    }}>
      <div><strong>🚀 REACT APP VERSION</strong></div>
      <div>Version: {version}</div>
      <div>Build: {buildTime}</div>
      <div>Container: #root</div>
      <div style={{color: '#00ff00'}}>✅ ACTIVE</div>
    </div>
  );
};

export default VersionInfo;
